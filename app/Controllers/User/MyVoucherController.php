<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\LocationModel;
use App\Models\VoucherIssueModel;

/**
 * MyVoucherController (User) — ประวัติ voucher ของผู้ใช้ที่ login อยู่
 * ใช้ดีไซน์เดียวกับหน้า Admin (ตาราง DataTables + modal ดูตั๋ว) แต่กรองเฉพาะของตัวเอง
 */
class MyVoucherController extends BaseController
{
    // palette สีประจำพื้นที่ (by index) — ใช้กับจุดสี/avatar
    private array $palette = ['#3B7DDD', '#0EA66B', '#E0930B', '#8B5CF6', '#E5484D', '#0E8FA6'];

    // หน้า list (โครง + dropdown ตัวกรอง) — ข้อมูลตารางโหลดผ่าน data()
    public function index()
    {
        $locModel = new LocationModel();

        return view('user/myvoucher/index', [
            'title'     => lang('Voucher.myTitle'),
            'subtitle'  => lang('Voucher.mySub'),
            'active'    => 'myvoucher',
            'locations' => $locModel->findAll(),
        ]);
    }

    // ข้อมูลตารางสำหรับ DataTables (server-side) — เฉพาะ voucher ของผู้ใช้ที่ login → JSON
    public function data()
    {
        helper(['netpass', 'url']);

        $userId = auth()->user()->id;
        $req    = $this->request;
        $draw   = (int) $req->getGet('draw');
        $start  = (int) $req->getGet('start');
        $length = (int) $req->getGet('length');
        $length = $length > 0 ? $length : 10;
        $search = trim((string) ($req->getGet('search')['value'] ?? ''));
        $loc    = (int) $req->getGet('loc');
        $status = (string) $req->getGet('status');

        // map ดัชนีคอลัมน์ DataTables → คอลัมน์ SQL ที่ sort ได้
        // คอลัมน์ 0 = checkbox, ไม่มีคอลัมน์ผู้ขอ (หน้า user เห็นเฉพาะของตัวเอง)
        $orderCols = [
            1 => 'voucher_issues.guest_voucher',
            2 => 'loc_name',
            3 => 'voucher_issues.duration',
            4 => 'voucher_issues.issued_at',
            5 => 'voucher_issues.expires_at',
            6 => 'voucher_issues.status',
        ];
        $orderIdx = (int) ($req->getGet('order')[0]['column'] ?? 4);
        $orderDir = strtolower((string) ($req->getGet('order')[0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $orderBy  = $orderCols[$orderIdx] ?? 'voucher_issues.issued_at';

        $issueModel = new VoucherIssueModel();

        // จำนวนทั้งหมดของผู้ใช้ (ไม่กรอง search/filter)
        $recordsTotal = $issueModel->where('voucher_issues.issued_by', $userId)->countAllResults();

        // builder หลัก + join + กรองเฉพาะของผู้ใช้
        $builder = $issueModel
            ->select('voucher_issues.id, voucher_issues.code, voucher_issues.voucher_id, voucher_issues.location_id, voucher_issues.duration, voucher_issues.guest_name, voucher_issues.supplier, voucher_issues.guest_firstname, voucher_issues.guest_lastname, voucher_issues.guest_phone, voucher_issues.guest_voucher, voucher_issues.issued_by, voucher_issues.issued_at, voucher_issues.expires_at, voucher_issues.status, locations.name AS loc_name, locations.name_en AS loc_name_en, locations.ssid AS loc_ssid, vouchers.vou_password AS vou_password, users.username AS req_username, users.firstname AS req_firstname, users.lastname AS req_lastname, users.img AS req_img')
            ->join('locations', 'locations.id = voucher_issues.location_id', 'left')
            ->join('vouchers', 'vouchers.id = voucher_issues.voucher_id', 'left')
            ->join('users', 'users.id = voucher_issues.issued_by', 'left')
            ->where('voucher_issues.issued_by', $userId);

        if ($loc > 0) {
            $builder->where('voucher_issues.location_id', $loc);
        }
        // กรองตามสถานะจริง: active = ยังไม่หมดอายุ, expired = หมดอายุแล้ว/ไม่ active
        $now = date('Y-m-d H:i:s');
        if ($status === 'active') {
            $builder->where('voucher_issues.status', 'active')
                ->where('voucher_issues.expires_at >', $now);
        } elseif ($status === 'expired') {
            $builder->groupStart()
                ->where('voucher_issues.status !=', 'active')
                ->orWhere('voucher_issues.expires_at <=', $now)
                ->groupEnd();
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('voucher_issues.code', $search)
                ->orLike('voucher_issues.guest_voucher', $search)
                ->orLike('voucher_issues.guest_name', $search)
                ->groupEnd();
        }

        // จำนวนหลังกรอง (clone ก่อน limit)
        $recordsFiltered = $builder->countAllResults(false);

        $rows = $builder->orderBy($orderBy, $orderDir)
            ->limit($length, $start)
            ->get()->getResultArray();

        $isEn = service('request')->getLocale() === 'en';
        $data = array_map(fn ($row) => $this->renderRow($row, $isEn), $rows);

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    // สร้าง HTML ของแต่ละแถว — markup เดียวกับหน้า Admin
    private function renderRow(array $row, bool $isEn): array
    {
        $username = $row['req_username'] ?: ($row['guest_name'] ?: 'Guest user');
        $reqFull  = trim(($row['req_firstname'] ?? '') . ' ' . ($row['req_lastname'] ?? ''));
        $name     = $reqFull !== '' ? $reqFull : $username;   // ใช้แสดงในช่อง "ผู้ขอ" ของ modal
        $ln       = $isEn ? (($row['loc_name_en'] ?? '') ?: $row['loc_name']) : $row['loc_name'];

        // สีพื้นที่ — by index ของ location_id ในชุด palette
        $locModel = new LocationModel();
        static $colorMap = null;
        if ($colorMap === null) {
            $colorMap = [];
            foreach ($locModel->findAll() as $i => $location) {
                $colorMap[(int) $location['id']] = $this->palette[$i % count($this->palette)];
            }
        }
        $color = $colorMap[(int) $row['location_id']] ?? $this->palette[0];

        $ok     = voucher_active($row['status'], $row['expires_at']);   // active = ยังไม่หมดอายุ
        $dur    = duration_label($row['duration']);
        $statTx = $ok ? lang('Voucher.statusActive') : lang('Voucher.statusExpired');

        // คอลัมน์ 1: username Wi-Fi
        $c1 = '<span class="font-mono" style="font-size:12.5px;color:var(--np-text-2)">' . esc($row['guest_voucher']) . '</span>';

        // คอลัมน์ 2: พื้นที่ + จุดสี
        $c2 = '<span class="d-inline-flex align-items-center gap-2">'
            . '<span class="np-dot" style="background:' . esc($color, 'attr') . '"></span>'
            . '<span class="text-truncate" style="max-width:170px">' . esc($ln) . '</span></span>';

        // คอลัมน์ 3: ระยะเวลา (pill)
        $c3 = '<span class="np-pill np-pill-blue">' . esc($dur) . '</span>';

        // คอลัมน์ 4-5: วันที่
        $c4 = '<span class="np-stat-sub" style="white-space:nowrap">' . esc($row['issued_at']) . '</span>';
        $c5 = '<span class="np-stat-sub" style="white-space:nowrap">' . esc($row['expires_at']) . '</span>';

        // คอลัมน์ 6: สถานะ
        $c6 = '<span class="np-badge ' . ($ok ? 'np-badge-ok' : 'np-badge-danger') . '">' . $statTx . '</span>';

        // คอลัมน์ 7: ปุ่มดู voucher (เปิด modal) — เก็บ data-attribute ครบ
        $attr = static fn ($value) => esc((string) $value, 'attr');
        $c7 = '<button type="button" class="np-icon-sm" title="' . $attr(lang('Voucher.viewVoucher')) . '" aria-label="' . $attr(lang('Voucher.viewVoucher')) . '"'
            . ' data-bs-toggle="modal" data-bs-target="#voucherModal"'
            . ' data-code="' . $attr($row['code']) . '"'
            . ' data-loc="' . $attr($ln) . '"'
            . ' data-user="' . $attr($row['guest_voucher']) . '"'
            . ' data-pass="' . $attr($row['vou_password'] ?? '') . '"'
            . ' data-ssid="' . $attr($row['loc_ssid'] ?? '') . '"'
            . ' data-color="' . $attr($color) . '"'
            . ' data-name="' . $attr($name) . '"'
            . ' data-dur="' . $attr($dur) . '"'
            . ' data-issued="' . $attr($row['issued_at']) . '"'
            . ' data-expires="' . $attr($row['expires_at']) . '"'
            . ' data-status="' . $attr($statTx) . '"'
            . ' data-ok="' . ($ok ? '1' : '0') . '"'
            . ' data-supplier="' . $attr($row['supplier'] ?? '') . '"'
            . ' data-guestfull="' . $attr(trim(($row['guest_firstname'] ?? '') . ' ' . ($row['guest_lastname'] ?? ''))) . '"'
            . ' data-phone="' . $attr($row['guest_phone'] ?? '') . '">'
            . '<i class="bi bi-eye"></i></button>';

        // คอลัมน์เลือก (checkbox) — value = id ของ voucher issue ใช้พิมพ์ตั๋วหลายใบ
        $cChk = '<input type="checkbox" class="form-check-input vch-pick" value="' . (int) $row['id'] . '" aria-label="select">';

        return [$cChk, $c1, $c2, $c3, $c4, $c5, $c6, $c7];
    }

    // คืนข้อมูลตั๋วของ id ที่เลือก (เฉพาะของผู้ใช้เอง) → JSON สำหรับพิมพ์หลายใบ
    public function tickets()
    {
        helper('netpass');

        $userId = auth()->user()->id;
        $ids    = $this->request->getGet('ids');
        $ids    = is_array($ids) ? array_values(array_filter(array_map('intval', $ids), fn ($v) => $v > 0)) : [];
        if ($ids === []) {
            return $this->response->setJSON(['ok' => false, 'tickets' => []]);
        }

        $isEn = service('request')->getLocale() === 'en';
        $rows = (new VoucherIssueModel())
            ->select('voucher_issues.*, locations.name AS loc_name, locations.name_en AS loc_name_en, locations.ssid AS loc_ssid, vouchers.vou_password AS vou_password')
            ->join('locations', 'locations.id = voucher_issues.location_id', 'left')
            ->join('vouchers', 'vouchers.id = voucher_issues.voucher_id', 'left')
            ->whereIn('voucher_issues.id', $ids)
            ->where('voucher_issues.issued_by', $userId)   // กันพิมพ์ตั๋วของคนอื่น
            ->findAll();

        $colorMap = [];
        foreach ((new LocationModel())->findAll() as $i => $location) {
            $colorMap[(int) $location['id']] = $this->palette[$i % count($this->palette)];
        }

        $tickets = array_map(function ($row) use ($isEn, $colorMap) {
            $ln = $isEn ? (($row['loc_name_en'] ?? '') ?: $row['loc_name']) : $row['loc_name'];
            return [
                'ssid'    => $row['loc_ssid'] ?? '',
                'user'    => $row['guest_voucher'],
                'pass'    => $row['vou_password'] ?? '',
                'loc'     => $ln,
                'dur'     => duration_label($row['duration']),
                'expires' => $row['expires_at'],
                'color'   => $colorMap[(int) $row['location_id']] ?? $this->palette[0],
            ];
        }, $rows);

        return $this->response->setJSON(['ok' => true, 'tickets' => $tickets]);
    }
}
