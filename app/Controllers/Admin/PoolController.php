<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LocationModel;
use App\Models\VoucherLotModel;
use App\Services\ActivityLog;
use App\Models\VoucherModel;

/**
 * PoolController — คลัง Voucher (Voucher Pool): สรุปแยกพื้นที่ + รายละเอียด + import/add/edit/delete
 */
class PoolController extends BaseController
{
    // palette สีประจำพื้นที่ (by index) — ใช้กับจุดสี
    private array $palette = ['#3B7DDD', '#0EA66B', '#E0930B', '#8B5CF6', '#E5484D', '#0E8FA6'];

    // หน้าสรุป (โครง + ข้อมูลสำหรับ modal) — ตารางโหลดผ่าน poolData()
    public function index()
    {
        return view('admin/pool/index', [
            'title'     => lang('Pool.title'),
            'subtitle'  => lang('Pool.subtitle'),
            'active'    => 'pool',
            'locations' => (new LocationModel())->orderBy('name', 'ASC')->findAll(),  // ใช้ใน dropdown ของ modal
            'durations' => voucher_durations(),
        ]);
    }

    // ข้อมูลตารางสรุปสต็อกแยกพื้นที่ (DataTables server-side) → JSON
    public function poolData()
    {
        helper('url');
        $req    = $this->request;
        $draw   = (int) $req->getGet('draw');
        $start  = (int) $req->getGet('start');
        $length = (int) ($req->getGet('length') ?: 10);
        $search = trim((string) ($req->getGet('search')['value'] ?? ''));
        $isEn   = service('request')->getLocale() === 'en';

        // คอลัมน์ที่เรียงได้: 0 ชื่อพื้นที่, 1 คงเหลือ, 2 จ่ายแล้ว, 3 รวม
        $orderCols = [0 => 'name', 1 => 'instock', 2 => 'issued', 3 => 'total'];
        $orderIdx  = (int) ($req->getGet('order')[0]['column'] ?? 0);
        $orderDir  = strtolower((string) ($req->getGet('order')[0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $orderBy   = $orderCols[$orderIdx] ?? 'name';

        $locModel = new LocationModel();
        $recordsTotal = $locModel->countAllResults();

        $builder = $locModel;
        if ($search !== '') {
            $builder->groupStart()->like('name', $search)->orLike('name_en', $search)->orLike('ssid', $search)->groupEnd();
        }
        // ดึงพื้นที่ทั้งหมดที่ผ่านการค้นหา (จำนวนพื้นที่ไม่มาก) เพื่อเรียงตาม count แล้วแบ่งหน้าใน PHP
        $rows = $builder->findAll();
        $recordsFiltered = count($rows);

        // นับ voucher แยกสถานะของแต่ละพื้นที่
        $counts = [];
        foreach (
            db_connect()->table('vouchers')
                ->select("location_id, SUM(status='instock') AS instock, SUM(status='issued') AS issued, COUNT(*) AS total")
                ->groupBy('location_id')->get()->getResultArray() as $countRow
        ) {
            $counts[(int) $countRow['location_id']] = $countRow;
        }

        // ผูกจำนวน voucher เข้ากับแต่ละพื้นที่ ใช้เป็นค่าที่เรียงได้
        foreach ($rows as &$row) {
            $c = $counts[(int) $row['id']] ?? ['instock' => 0, 'issued' => 0, 'total' => 0];
            $row['instock'] = (int) $c['instock'];
            $row['issued']  = (int) $c['issued'];
            $row['total']   = (int) $c['total'];
        }
        unset($row);

        // เรียงตามคอลัมน์ที่หัวตารางเลือก (ชื่อ = ตัวอักษร, count = ตัวเลข)
        usort($rows, function ($a, $b) use ($orderBy, $orderDir) {
            $cmp = $orderBy === 'name'
                ? strcasecmp((string) $a['name'], (string) $b['name'])
                : $a[$orderBy] <=> $b[$orderBy];
            return $orderDir === 'DESC' ? -$cmp : $cmp;
        });

        // แบ่งหน้าหลังเรียงเสร็จ
        $rows = array_slice($rows, $start, $length);

        // สีคงที่ทุกหน้า: อิงลำดับ id (เรียงตาม name)
        $rankById = array_flip(array_column($locModel->orderBy('name', 'ASC')->findAll(), 'id'));

        $data = array_map(function ($location) use ($rankById, $isEn) {
            $color = $this->palette[($rankById[$location['id']] ?? 0) % count($this->palette)];
            $name  = $isEn ? (($location['name_en'] ?? '') ?: $location['name']) : (($location['name'] ?? '') ?: $location['name_en']);
            $attr  = static fn ($value) => esc((string) $value, 'attr');

            return [
                '<div class="d-flex align-items-center gap-2">'
                    . '<span class="np-dot" style="background:' . $color . '"></span>'
                    . '<div class="min-w-0"><div class="fw-semibold">' . esc($name) . '</div>'
                    . '<div class="np-stat-sub">' . esc(lang('Pool.ssid')) . ': ' . esc($location['ssid']) . '</div></div></div>',
                '<span class="fw-bold" style="color:var(--np-ok-fg)">' . (int) $location['instock'] . '</span>',
                '<span style="color:var(--np-muted-2)">' . (int) $location['issued'] . '</span>',
                '<span class="fw-semibold" style="color:var(--np-text-2)">' . (int) $location['total'] . '</span>',
                // จัดการ: ดู (เข้าหน้ารายละเอียด) / แก้ไข Location / ลบ Location
                '<a href="' . site_url('admin/pool/location/' . $location['id']) . '" class="np-icon-sm" title="' . $attr(lang('Pool.view')) . '"><i class="bi bi-eye"></i></a>'
                    . '<button class="np-icon-sm ms-1" title="' . $attr(lang('Common.edit')) . '" data-bs-toggle="modal" data-bs-target="#editModal"'
                    . ' data-id="' . $attr($location['id']) . '" data-name="' . $attr($location['name']) . '"'
                    . ' data-name-en="' . $attr($location['name_en'] ?? '') . '" data-ssid="' . $attr($location['ssid']) . '"><i class="bi bi-pencil"></i></button>'
                    . '<button class="np-icon-sm np-danger ms-1" title="' . $attr(lang('Common.delete')) . '" data-bs-toggle="modal" data-bs-target="#deleteModal"'
                    . ' data-id="' . $attr($location['id']) . '" data-name="' . $attr($name) . '"><i class="bi bi-trash"></i></button>',
            ];
        }, $rows);

        return $this->response->setJSON([
            'draw' => $draw, 'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered, 'data' => $data,
        ]);
    }

    // หน้ารายละเอียด voucher ของ location หนึ่ง (โครง — ข้อมูลโหลดผ่าน detailData())
    public function detail($locId)
    {
        $location = (new LocationModel())->find($locId);
        if (! $location) {
            return redirect()->to('admin/pool');
        }

        $isEn = service('request')->getLocale() === 'en';

        return view('admin/pool/detail', [
            'title'    => $isEn ? ($location['name_en'] ?: $location['name']) : ($location['name'] ?: $location['name_en']),
            'subtitle' => lang('Pool.subtitle'),
            'active'   => 'pool',
            'location' => $location,
        ]);
    }

    // ข้อมูลตาราง voucher ของ location (DataTables server-side) → JSON
    public function detailData($locId)
    {
        helper(['netpass', 'form']);
        $req    = $this->request;
        $draw   = (int) $req->getGet('draw');
        $start  = (int) $req->getGet('start');
        $length = (int) ($req->getGet('length') ?: 10);
        $search = trim((string) ($req->getGet('search')['value'] ?? ''));
        $status = (string) $req->getGet('status');

        $orderCols = [0 => 'id', 1 => 'vou_username', 3 => 'duration', 4 => 'status', 5 => 'created_at'];
        $orderIdx  = (int) ($req->getGet('order')[0]['column'] ?? 0);
        $orderDir  = strtolower((string) ($req->getGet('order')[0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $orderBy   = $orderCols[$orderIdx] ?? 'id';

        $voucherModel = new VoucherModel();

        $recordsTotal = $voucherModel->where('location_id', $locId)->countAllResults();

        $builder = $voucherModel->where('location_id', $locId);
        if (in_array($status, ['instock', 'issued'], true)) {
            $builder->where('status', $status);
        }
        if ($search !== '') {
            $builder->like('vou_username', $search);
        }
        $recordsFiltered = $builder->countAllResults(false);

        // ค่าเริ่มต้น (ผู้ใช้ไม่ได้คลิกเรียงเอง): instock ขึ้นก่อน issued ไว้ท้าย แล้ว FIFO (LOT แรกที่นำเข้าก่อน) ในแต่ละกลุ่ม
        if (empty($req->getGet('order'))) {
            $builder->orderBy("(status = 'issued')", 'ASC', false)->orderBy('id', 'ASC');
        } else {
            $builder->orderBy($orderBy, $orderDir);
        }
        $rows = $builder->limit($length, $start)->get()->getResultArray();

        $data = [];
        foreach ($rows as $i => $row) {
            $data[] = $this->renderVoucherRow($row, $start + $i + 1);
        }

        return $this->response->setJSON([
            'draw' => $draw, 'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered, 'data' => $data,
        ]);
    }

    // สร้าง HTML 6 คอลัมน์ (ลำดับ / username / password / ระยะเวลา / สถานะ / จัดการ)
    private function renderVoucherRow(array $voucher, int $no): array
    {
        $attr    = static fn ($value) => esc((string) $value, 'attr');
        $instock = $voucher['status'] === 'instock';

        $c0 = '<span class="np-stat-sub">' . $no . '</span>';
        $c1 = '<span class="font-mono fw-semibold">' . esc($voucher['vou_username']) . '</span>';
        $c2 = '<span class="font-mono" style="color:var(--np-muted);letter-spacing:.5px">' . esc($voucher['vou_password']) . '</span>';
        $c3 = '<span class="np-pill np-pill-blue">' . esc(duration_label($voucher['duration'])) . '</span>';
        $c4 = '<span class="np-badge ' . ($instock ? 'np-badge-ok' : 'np-badge-muted') . '">'
            . ($instock ? lang('Common.instock') : lang('Common.issued')) . '</span>';

        $cDate = '<span class="np-stat-sub" style="white-space:nowrap">' . esc($voucher['created_at'] ?? '') . '</span>';

        $c5 = '';
        if ($instock) {
            $c5 = '<div class="d-flex gap-1 justify-content-end">'
                . '<button type="button" class="np-icon-sm" data-bs-toggle="modal" data-bs-target="#editModal"'
                . ' data-id="' . $attr($voucher['id']) . '" data-user="' . $attr($voucher['vou_username']) . '" data-pass="' . $attr($voucher['vou_password']) . '"'
                . ' title="' . $attr(lang('Common.edit')) . '"><i class="bi bi-pencil"></i></button>'
                . '<button type="button" class="np-icon-sm np-danger" data-bs-toggle="modal" data-bs-target="#vDeleteModal"'
                . ' data-id="' . $attr($voucher['id']) . '" data-user="' . $attr($voucher['vou_username']) . '"'
                . ' title="' . $attr(lang('Common.delete')) . '"><i class="bi bi-trash3"></i></button></div>';
        }

        return [$c0, $c1, $c2, $c3, $c4, $cDate, $c5];
    }

    // ดาวน์โหลด template CSV (username,password)
    // ค่าตัวอย่างผ่าน csv_safe() ทุก cell เพื่อกัน formula injection (template ปลอดภัย)
    public function importTemplate()
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['username', 'password']);
        fputcsv($out, array_map('csv_safe', ['guest-1234', 'AB12CD34']));
        fputcsv($out, array_map('csv_safe', ['guest-5678', 'XY98ZW76']));
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="voucher-template.csv"')
            ->setBody($csv);
    }

    // นำเข้า voucher จากไฟล์ CSV (all-or-nothing) → ตอบ JSON
    public function import()
    {
        $jsonErr = fn (string $msg) => $this->response->setJSON(['ok' => false, 'error' => $msg, 'csrf' => csrf_hash()]);

        $locId    = (int) $this->request->getPost('location_id');
        $duration = (string) $this->request->getPost('duration');
        if ($locId <= 0 || ! isset(voucher_durations()[$duration])) {
            return $jsonErr(lang('Pool.invalidDuration'));
        }

        // ตรวจไฟล์: ต้องมี, นามสกุล csv, ≤ 2MB
        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid() || strtolower($file->getClientExtension()) !== 'csv' || $file->getSize() > 2 * 1024 * 1024) {
            return $jsonErr(lang('Pool.importBadFile'));
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) file_get_contents($file->getTempName()));

        $seen = [];   // username ตัวพิมพ์เล็ก → กันซ้ำในไฟล์
        $rows = [];   // [['user'=>, 'pass'=>, 'line'=>], ...]
        foreach ($lines as $idx => $line) {
            $lineNo = $idx + 1;
            $trim   = trim($line);
            if ($trim === '') { continue; }   // ข้ามบรรทัดว่าง

            $parts = array_map('trim', preg_split('/[\t,;]+/', $trim));
            // ข้าม header แถวแรกที่ดูเป็นหัวคอลัมน์
            if ($idx === 0 && preg_match('/user|ชื่อ/i', $parts[0] ?? '') && preg_match('/pass|รหัส/i', $parts[1] ?? '')) {
                continue;
            }

            $user = $parts[0] ?? '';
            $pass = $parts[1] ?? '';
            if ($user === '') { return $jsonErr(lang('Pool.importErrUserReq', [$lineNo])); }
            if ($pass === '') { return $jsonErr(lang('Pool.importErrPassReq', [$lineNo])); }
            if (mb_strlen($user) > 50 || mb_strlen($pass) > 50) { return $jsonErr(lang('Pool.importErrTooLong', [$lineNo])); }

            $key = mb_strtolower($user);
            if (isset($seen[$key])) { return $jsonErr(lang('Pool.importErrUserDup', [$lineNo, $user])); }
            $seen[$key] = true;

            $rows[] = ['user' => $user, 'pass' => $pass, 'line' => $lineNo];
        }

        if ($rows === [])          { return $jsonErr(lang('Pool.importEmptyFile')); }
        if (count($rows) > 1000)   { return $jsonErr(lang('Pool.importTooMany')); }

        // ตรวจซ้ำกับ voucher เดิมใน location ที่เลือก
        $voucherModel = new VoucherModel();
        $existing = $voucherModel->where('location_id', $locId)
            ->whereIn('vou_username', array_column($rows, 'user'))
            ->findColumn('vou_username') ?? [];
        $existSet = array_map('mb_strtolower', $existing);
        foreach ($rows as $r) {
            if (in_array(mb_strtolower($r['user']), $existSet, true)) {
                return $jsonErr(lang('Pool.importErrUserExists', [$r['line'], $r['user']]));
            }
        }

        // ผ่านหมด → สร้าง lot + insert
        $lotId = $this->newLot($locId, $duration, count($rows));
        $batch = [];
        foreach ($rows as $r) {
            $batch[] = [
                'lot_id' => $lotId, 'location_id' => $locId, 'duration' => $duration,
                'vou_username' => $r['user'], 'vou_password' => $r['pass'], 'status' => 'instock',
            ];
        }
        $voucherModel->insertBatch($batch);

        // สรุปผลสำหรับ modal: ชื่อพื้นที่ (ตาม locale) + SSID + label ระยะเวลา
        $loc     = (new LocationModel())->find($locId);
        $isEn    = service('request')->getLocale() === 'en';
        $locName = $isEn ? (($loc['name_en'] ?? '') ?: ($loc['name'] ?? '')) : (($loc['name'] ?? '') ?: ($loc['name_en'] ?? ''));

        // เก็บ log เป็น English คงที่ — ชื่อพื้นที่ name_en, duration key ดิบ
        $logLoc = ($loc['name_en'] ?? '') ?: ($loc['name'] ?? '');
        ActivityLog::record('voucher.import', [
            'target_type'  => 'location',
            'target_id'    => $locId,
            'target_label' => $logLoc,
            'details'      => [
                'location' => $logLoc,
                'ssid'     => $loc['ssid'] ?? null,
                'duration' => $duration,
                'imported' => count($rows),
                'lot_id'   => $lotId,
            ],
        ]);

        return $this->response->setJSON([
            'ok'       => true,
            'imported' => count($rows),
            'location' => $locName,
            'ssid'     => $loc['ssid'] ?? '',
            'duration' => duration_label($duration),
            // ส่ง CSRF token ใหม่กลับ (regenerate=true หมุน token ทุก request) เพื่อให้ import ครั้งถัดไปไม่โดน 403
            'csrf'     => csrf_hash(),
        ]);
    }

    // เพิ่ม voucher ทีละใบ
    public function addVoucher()
    {
        if (! $this->validate([
            'location_id'  => 'required|is_natural_no_zero',
            'duration'     => 'required',
            'vou_username' => 'required|max_length[50]',
            'vou_password' => 'required|max_length[50]',
        ])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $locId    = (int) $this->request->getPost('location_id');
        $duration = (string) $this->request->getPost('duration');
        if (! isset(voucher_durations()[$duration])) {
            return redirect()->back()->with('error', lang('Pool.invalidDuration'));
        }

        $lotId = $this->newLot($locId, $duration, 1);
        $vModel = new VoucherModel();
        $vId = $vModel->insert([
            'lot_id'       => $lotId,
            'location_id'  => $locId,
            'duration'     => $duration,
            'vou_username' => $this->request->getPost('vou_username'),
            'vou_password' => $this->request->getPost('vou_password'),
            'status'       => 'instock',
        ]);

        ActivityLog::record('voucher.add', [
            'target_type'  => 'voucher',
            'target_id'    => $vId,
            'target_label' => $this->request->getPost('vou_username'),
            'details'      => [
                'username'    => $this->request->getPost('vou_username'),
                'location_id' => $locId,
                'duration'    => $duration,
            ],
        ]);

        return redirect()->to('admin/pool/location/' . $locId)->with('message', lang('Pool.voucherAdded'));
    }

    // แก้ไข voucher (username/password)
    public function updateVoucher($id)
    {
        $voucherModel = new VoucherModel();
        $voucher      = $voucherModel->find($id);
        if (! $voucher) {
            return redirect()->to('admin/pool');
        }
        if (! $this->validate(['vou_username' => 'required|max_length[50]', 'vou_password' => 'required|max_length[50]'])) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $voucherModel->update($id, [
            'vou_username' => $this->request->getPost('vou_username'),
            'vou_password' => $this->request->getPost('vou_password'),
        ]);

        ActivityLog::record('voucher.update', [
            'target_type'  => 'voucher',
            'target_id'    => $id,
            'target_label' => $this->request->getPost('vou_username'),
            'details'      => [
                'before' => ['username' => $voucher['vou_username'], 'password' => $voucher['vou_password']],
                'after'  => ['username' => $this->request->getPost('vou_username'), 'password' => $this->request->getPost('vou_password')],
            ],
        ]);

        return redirect()->to('admin/pool/location/' . $voucher['location_id'])->with('message', lang('Pool.voucherUpdated'));
    }

    // ลบ voucher (เฉพาะที่ยังไม่ถูกจ่าย — issued มี FK ในประวัติ)
    public function deleteVoucher($id)
    {
        $voucherModel = new VoucherModel();
        $voucher      = $voucherModel->find($id);
        if (! $voucher) {
            return redirect()->to('admin/pool');
        }
        if ($voucher['status'] === 'issued') {
            return redirect()->to('admin/pool/location/' . $voucher['location_id'])->with('error', lang('Pool.cannotDeleteIssued'));
        }
        $voucherModel->delete($id);

        ActivityLog::record('voucher.delete', [
            'target_type'  => 'voucher',
            'target_id'    => $id,
            'target_label' => $voucher['vou_username'],
            'details'      => [
                'username'    => $voucher['vou_username'],
                'location_id' => $voucher['location_id'],
                'duration'    => $voucher['duration'],
            ],
        ]);

        return redirect()->to('admin/pool/location/' . $voucher['location_id'])->with('message_danger', lang('Pool.voucherDeleted'));
    }

    // สร้าง lot ใหม่ + คืน id — code รูปแบบ LOT-yyyy-xxxx (xxxx นับต่อปี รีเซ็ตทุกปี, 4 หลัก)
    private function newLot(int $locId, string $duration, int $qty): int
    {
        $lotModel = new VoucherLotModel();
        $prefix   = 'LOT-' . date('Y') . '-';
        // ลำดับถัดไป = เลขสูงสุดของปีนี้ + 1 (อิงค่าสูงสุดเพื่อกันชนกรณีมีการลบ lot)
        $last = $lotModel->like('code', $prefix, 'after')->orderBy('code', 'DESC')->first();
        $seq  = $last ? ((int) substr($last['code'], strlen($prefix)) + 1) : 1;
        $code = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        while ($lotModel->where('code', $code)->first()) {
            $seq++;
            $code = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        }
        $lotModel->insert([
            'code' => $code, 'location_id' => $locId, 'duration' => $duration, 'qty' => $qty, 'status' => 'active',
        ]);

        return (int) $lotModel->getInsertID();
    }

    // แยกข้อความที่วางมาเป็นแถว [username, password]
    private function parseRows($text): array
    {
        $out   = [];
        $lines = preg_split('/\r\n|\r|\n/', (string) $text);
        foreach ($lines as $i => $line) {
            $parts = array_map('trim', preg_split('/[\t,;]+/', trim($line)));
            if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                continue;
            }
            if ($i === 0 && preg_match('/user|ชื่อ/i', $parts[0]) && preg_match('/pass|รหัส/i', $parts[1])) {
                continue;
            }
            $out[] = [$parts[0], $parts[1]];
        }

        return $out;
    }
}
