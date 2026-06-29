<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ActivityLogModel;

/**
 * ActivityLogController — หน้า "บันทึกการใช้งาน" (audit trail)
 * index() ส่งโครงหน้า, data() ส่ง JSON ให้ DataTables, export() ดาวน์โหลด CSV
 *
 * หมายเหตุ: เนื้อหา log เป็น "English คงที่" เสมอ (ไม่แปลตามภาษา) — label/ประเภท
 * ฝังเป็น English ในโค้ด, ส่วนค่าข้อมูล (ชื่อ/รายละเอียด) เก็บ text ตรงจาก DB ตามที่บันทึกไว้
 */
class ActivityLogController extends BaseController
{
    // [tone สี, ไอคอน, label English คงที่] ตามชนิด action
    private array $meta = [
        'auth.login'            => ['info',    'box-arrow-in-right', 'Sign in'],
        'auth.logout'           => ['muted',   'box-arrow-right',    'Sign out'],
        'auth.password_change'  => ['warning', 'key',                'Change password'],
        'voucher.request'       => ['info',    'ticket-detailed',    'Request voucher'],
        'voucher.import'        => ['teal',    'box-arrow-in-down',  'Import vouchers'],
        'voucher.add'           => ['teal',    'plus-lg',            'Add voucher'],
        'voucher.update'        => ['amber',   'pencil',             'Edit voucher'],
        'voucher.delete'        => ['danger',  'trash',              'Delete voucher'],
        'location.create'       => ['teal',    'geo-alt',            'Create location'],
        'location.update'       => ['amber',   'pencil',             'Edit location'],
        'location.delete'       => ['danger',  'trash',              'Delete location'],
        'member.create'         => ['teal',    'person-plus',        'Create member'],
        'member.update'         => ['amber',   'pencil',             'Edit member'],
        'member.delete'         => ['danger',  'trash',              'Delete member'],
        'member.toggle'         => ['warning', 'power',              'Toggle account'],
        'member.reset_password' => ['warning', 'key',                'Reset password'],
        'profile.update'        => ['info',    'person',             'Edit profile'],
    ];

    // ประเภทรายการ — English คงที่
    private array $typeLabels = [
        'voucher'  => 'Voucher',
        'location' => 'Location',
        'member'   => 'Member',
        'request'  => 'Request',
    ];

    // หน้า list (โครง + ตัวกรอง) — ข้อมูลโหลดผ่าน data()
    public function index()
    {
        // dropdown ตัวกรอง: key => label(English)
        $actions = [];
        foreach ($this->meta as $key => $m) {
            $actions[$key] = $m[2];
        }

        return view('admin/logs/index', [
            'title'    => lang('Activity.title'),
            'subtitle' => lang('Activity.subtitle'),
            'active'   => 'logs',
            'actions'  => $actions,
        ]);
    }

    // ข้อมูลตารางสำหรับ DataTables (server-side) → คืน JSON
    public function data()
    {
        $req    = $this->request;
        $draw   = (int) $req->getGet('draw');
        $start  = (int) $req->getGet('start');
        $length = (int) $req->getGet('length');
        $length = $length > 0 ? $length : 10;

        $orderCols = [
            0 => 'created_at',
            1 => 'actor_name',
            2 => 'actor_role',
            3 => 'actor_username',
            4 => 'action',
            5 => 'target_type',
            6 => 'target_label',
            8 => 'ip_address',
        ];
        $orderIdx  = (int) ($req->getGet('order')[0]['column'] ?? 0);
        $orderDir  = strtolower((string) ($req->getGet('order')[0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $orderBy   = $orderCols[$orderIdx] ?? 'created_at';

        $total    = (new ActivityLogModel())->countAllResults();
        $builder  = $this->applyFilters(new ActivityLogModel());
        $filtered = $builder->countAllResults(false);
        $rows     = $builder->orderBy($orderBy, $orderDir)->findAll($length, $start);

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => array_map([$this, 'renderRow'], $rows),
        ]);
    }

    // ดาวน์โหลด CSV (UTF-8 BOM → Excel อ่านได้) ตามตัวกรองปัจจุบัน
    // คอลัมน์: Date/Time | Username | Action | Target type | Details | IP
    public function export()
    {
        // บังคับเลือกช่วงวันที่ก่อน export (กันเรียก URL ตรงๆ โดยไม่มี period)
        if ((string) $this->request->getGet('date_from') === '' || (string) $this->request->getGet('date_to') === '') {
            return redirect()->to(site_url('admin/logs'))->with('error', lang('Activity.exportNeedPeriod'));
        }

        $rows = $this->applyFilters(new ActivityLogModel())
            ->orderBy('created_at', 'DESC')
            ->findAll(50000);

        $out = fopen('php://temp', 'r+');
        // คอลัมน์ให้ตรงกับตารางบนหน้าจอ
        fputcsv($out, ['Date/Time', 'User', 'Role', 'Username', 'Action', 'Target type', 'Target', 'Details', 'IP']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['created_at'],
                $r['actor_name'] ?? '',
                $r['actor_role'] ?? '',
                $r['actor_username'] ?? '',
                $this->actLabel($r['action']),
                $this->tgtLabel($r['target_type'] ?? ''),
                $r['target_label'] ?? '',
                $this->readableDetails($r),
                $r['ip_address'] ?? '',
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $filename = 'activity-log-' . date('Ymd-His') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    // ใส่ where ตามตัวกรอง (ใช้ร่วม data + export)
    private function applyFilters(ActivityLogModel $m): ActivityLogModel
    {
        $req       = $this->request;
        $action    = (string) $req->getGet('action');
        $from      = (string) $req->getGet('date_from');
        $to        = (string) $req->getGet('date_to');
        $searchRaw = $req->getGet('search');
        $search    = is_array($searchRaw) ? trim((string) ($searchRaw['value'] ?? '')) : trim((string) $searchRaw);

        if ($action !== '' && isset($this->meta[$action])) {
            $m->where('action', $action);
        }
        if ($from !== '') {
            $m->where('created_at >=', $from . ' 00:00:00');
        }
        if ($to !== '') {
            $m->where('created_at <=', $to . ' 23:59:59');
        }
        if ($search !== '') {
            $m->groupStart()
                ->like('actor_name', $search)
                ->orLike('actor_username', $search)
                ->orLike('target_label', $search)
                ->orLike('action', $search)
                ->groupEnd();
        }

        return $m;
    }

    // สร้าง HTML 10 คอลัมน์ (แถวเดียวแนวนอน ไม่ซ้อนบรรทัด):
    // เวลา · ผู้ใช้ · สิทธิ์ · Username · การกระทำ · ประเภท · รายการ · รายละเอียด · IP · ดู
    private function renderRow(array $r): array
    {
        [$tone, $icon] = $this->meta[$r['action']] ?? ['muted', 'dot'];
        $actLabel = $this->actLabel($r['action']);
        $hasActor = ! empty($r['actor_name']) || ! empty($r['actor_username']);

        // เวลา — บรรทัดเดียว
        $time = '<span class="np-log-dt">' . esc(date('d/m/Y', strtotime($r['created_at'])))
            . ' <b>' . esc(date('H:i', strtotime($r['created_at']))) . '</b></span>';

        // ผู้ใช้ (ชื่อ) / สิทธิ์ / Username — แยกคอลัมน์
        $userName = $hasActor
            ? '<span class="np-log-name">' . esc($r['actor_name'] ?: '—') . '</span>'
            : '<span class="text-muted">System</span>';
        $role = $r['actor_role']
            ? '<span class="np-log-role' . ($r['actor_role'] === 'admin' ? ' is-admin' : '') . '">' . esc($r['actor_role']) . '</span>'
            : '<span class="text-muted">—</span>';
        $username = $r['actor_username']
            ? '<span class="font-mono np-log-ip">@' . esc($r['actor_username']) . '</span>'
            : '<span class="text-muted">—</span>';

        // การกระทำ — ข้อความสีตาม tone (ไม่ใช้ badge)
        $badge = '<span class="np-log-act is-' . $tone . '"><i class="bi bi-' . $icon . '"></i>' . esc($actLabel) . '</span>';

        // ประเภท / รายการ — แยกคอลัมน์
        $tgtType  = $this->tgtLabel($r['target_type'] ?? '');
        $typeCell = $tgtType ? '<span class="np-log-type">' . esc($tgtType) . '</span>' : '<span class="text-muted">—</span>';
        $tgtCell  = $r['target_label'] ? '<span class="np-log-name">' . esc($r['target_label']) . '</span>' : '<span class="text-muted">—</span>';

        // รายละเอียด (อ่านง่าย, บรรทัดเดียว ตัดด้วย … + tooltip เต็ม) — ตรงกับ CSV
        $brief   = $this->readableDetails($r);
        $details = $brief !== ''
            ? '<span class="np-log-details" title="' . esc($brief, 'attr') . '">' . esc($brief) . '</span>'
            : '<span class="text-muted">—</span>';

        // IP — ล็อกความกว้างคอลัมน์ให้พอดี xxx.xxx.xxx.xxx
        $ip = $r['ip_address'] ? '<span class="font-mono np-log-ipaddr">' . esc($r['ip_address']) . '</span>' : '<span class="text-muted">—</span>';

        // ปุ่มดูรายละเอียดเต็ม — แนบ details(JSON) + บริบท ให้ JS เปิด modal
        $btn = '<button type="button" class="np-icon-sm np-log-view" title="' . esc(lang('Activity.view'), 'attr') . '"'
            . ' data-action="' . esc($actLabel, 'attr') . '"'
            . ' data-actor="' . esc(($r['actor_name'] ?: $r['actor_username']) ?: 'System', 'attr') . '"'
            . ' data-time="' . esc(date('d/m/Y H:i:s', strtotime($r['created_at'])), 'attr') . '"'
            . ' data-details="' . esc((string) ($r['details'] ?? ''), 'attr') . '">'
            . '<i class="bi bi-eye"></i></button>';

        return [$time, $userName, $role, $username, $badge, $typeCell, $tgtCell, $details, $ip, '<div class="text-end">' . $btn . '</div>'];
    }

    // label ของ action (English คงที่; fallback เป็น key)
    private function actLabel(string $action): string
    {
        return $this->meta[$action][2] ?? $action;
    }

    // label ของประเภทรายการ (English คงที่)
    private function tgtLabel(?string $type): string
    {
        if (! $type) {
            return '';
        }

        return $this->typeLabels[$type] ?? $type;
    }

    // รายละเอียดเบื้องต้น (ย่อ, English) สำหรับ CSV: ชื่อรายการ + ข้อมูลสำคัญสั้นๆ
    // รายละเอียดอ่านง่าย (English) — ใช้ทั้งตารางและ CSV ให้ตรงกัน (บรรทัดเดียว คั่นด้วย | )
    private function readableDetails(array $r): string
    {
        $d = json_decode($r['details'] ?? '', true);
        if (! is_array($d) || $d === []) {
            return '';
        }

        $out = [];

        // ก่อน → หลัง (เฉพาะที่เปลี่ยน) แสดงค่าจริงทั้งสองฝั่ง
        if (isset($d['before'], $d['after']) && is_array($d['before']) && is_array($d['after'])) {
            foreach ($d['after'] as $k => $v) {
                $old = $d['before'][$k] ?? '';
                if ($old !== $v) {
                    $out[] = $this->humanKey($k) . ': ' . $this->scalar($old) . ' → ' . $this->scalar($v);
                }
            }
        }

        // รายชื่อผู้รับ voucher (ชื่อ + เบอร์)
        if (isset($d['guests']) && is_array($d['guests'])) {
            $g = [];
            foreach ($d['guests'] as $x) {
                $name  = trim((string) ($x['name'] ?? ''));
                $phone = trim((string) ($x['phone'] ?? ''));
                if ($name !== '' || $phone !== '') {
                    $g[] = $name . ($phone !== '' ? ' (' . $phone . ')' : '');
                }
            }
            if ($g) {
                $out[] = 'Guests: ' . implode(', ', $g);
            }
        }

        // ฟิลด์ scalar อื่นๆ (ข้าม id / ค่าที่ซ้ำกับคอลัมน์อื่น)
        $skip = ['before', 'after', 'guests', 'lot_id', 'location_id', 'location'];
        foreach ($d as $k => $v) {
            if (in_array($k, $skip, true) || is_array($v) || $v === '' || $v === null) {
                continue;
            }
            $out[] = $this->humanKey($k) . ': ' . $this->scalar($v);
        }

        return implode(' | ', $out);
    }

    // key เทคนิค → อ่านง่าย: name_en → "Name en"
    private function humanKey(string $k): string
    {
        return ucfirst(str_replace('_', ' ', $k));
    }

    // ค่า scalar → ข้อความ (bool เป็น Yes/No)
    private function scalar($v): string
    {
        if (is_bool($v)) {
            return $v ? 'Yes' : 'No';
        }

        return (string) $v;
    }
}
