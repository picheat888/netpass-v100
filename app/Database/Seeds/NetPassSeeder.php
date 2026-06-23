<?php

namespace App\Database\Seeds;

use App\Models\LocationModel;
use App\Models\UserModel;
use App\Models\VoucherIssueModel;
use App\Models\VoucherLotModel;
use App\Models\VoucherModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

/**
 * NetPassSeeder — ข้อมูลตั้งต้น: admin + user, locations, lots, vouchers, issues
 */
class NetPassSeeder extends Seeder
{
    // สุ่มรหัส voucher
    private function rand(int $length = 8): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }

    public function run()
    {
        $users     = new UserModel();
        $locModel  = new LocationModel();
        $lotModel  = new VoucherLotModel();
        $vouModel  = new VoucherModel();
        $issModel  = new VoucherIssueModel();

        // ---------- ผู้ใช้งาน ----------
        // admin
        if ($users->where('username', 'admin')->first() === null) {
            $admin = new User([
                'username'  => 'admin',
                'firstname' => 'ผู้ดูแล',
                'lastname'  => 'ระบบ',
                'position'  => 'IT Admin',
                'active'    => 1,
            ]);
            $users->save($admin);
            $admin = $users->findById($users->getInsertID());
            $admin->createEmailIdentity(['email' => 'admin@netpass.local', 'password' => 'admin']);
            $admin->addGroup('admin');
        }
        $adminId = $users->where('username', 'admin')->first()->id;

        // user ทั่วไป
        if ($users->where('username', 'user')->first() === null) {
            $user = new User([
                'username'  => 'user',
                'firstname' => 'พนักงาน',
                'lastname'  => 'ทดสอบ',
                'position'  => 'Staff',
                'active'    => 1,
            ]);
            $users->save($user);
            $user = $users->findById($users->getInsertID());
            $user->createEmailIdentity(['email' => 'user@netpass.local', 'password' => 'user']);
            $user->addGroup('user');
        }

        // ---------- locations ----------
        $locations = [
            ['name' => 'สำนักงานใหญ่ (HQ)',     'name_en' => 'Head Office (HQ)',   'ssid' => 'NetPass-HQ'],
            ['name' => 'ห้องประชุม & Co-working', 'name_en' => 'Meeting & Co-working', 'ssid' => 'NetPass-Meeting'],
            ['name' => 'Lobby & ต้อนรับ',         'name_en' => 'Lobby & Reception',  'ssid' => 'NetPass-Lobby'],
            ['name' => 'โรงงาน / คลังสินค้า',     'name_en' => 'Factory / Warehouse', 'ssid' => 'NetPass-Factory'],
            ['name' => 'พื้นที่จัดงาน Event',     'name_en' => 'Event Space',         'ssid' => 'NetPass-Event'],
        ];
        $locIds = [];
        foreach ($locations as $loc) {
            $existing = $locModel->where('ssid', $loc['ssid'])->first();
            if ($existing) {
                $locIds[] = (int) $existing['id'];
                continue;
            }
            $locModel->insert($loc);
            $locIds[] = (int) $locModel->getInsertID();
        }

        // ---------- lots + vouchers + issues ----------
        // ถ้ามี lot อยู่แล้วข้าม (กันซ้ำตอน seed ซ้ำ)
        if ($lotModel->countAll() > 0) {
            return;
        }

        $durations = ['1d', '3d', '5d', '7d'];
        $plan      = [[15, 5], [12, 4], [18, 6], [8, 7], [20, 8]]; // [instock, issued] ต่อ location
        $lotNo     = 2401;

        foreach ($locIds as $i => $locId) {
            [$instock, $issued] = $plan[$i] ?? [10, 3];
            $duration = $durations[$i % count($durations)];

            $lotModel->insert([
                'code'        => 'LOT-' . $lotNo++,
                'location_id' => $locId,
                'duration'    => $duration,
                'qty'         => $instock + $issued,
                'status'      => 'active',
            ]);
            $lotId = (int) $lotModel->getInsertID();

            // instock
            for ($k = 0; $k < $instock; $k++) {
                $vouModel->insert([
                    'lot_id'       => $lotId,
                    'location_id'  => $locId,
                    'duration'     => $duration,
                    'vou_username' => 'guest-' . random_int(1000, 9999),
                    'vou_password' => $this->rand(8),
                    'status'       => 'instock',
                ]);
            }

            // issued + ประวัติ
            $hours = duration_hours($duration);
            for ($k = 0; $k < $issued; $k++) {
                $user = 'guest-' . random_int(1000, 9999);
                $pass = $this->rand(8);
                $vouModel->insert([
                    'lot_id'       => $lotId,
                    'location_id'  => $locId,
                    'duration'     => $duration,
                    'vou_username' => $user,
                    'vou_password' => $pass,
                    'status'       => 'issued',
                ]);
                $vouId = (int) $vouModel->getInsertID();

                $minsAgo   = random_int(5, 4000);
                $issuedAt  = date('Y-m-d H:i:s', time() - $minsAgo * 60);
                $expiresAt = date('Y-m-d H:i:s', strtotime($issuedAt) + $hours * 3600);
                $status    = strtotime($expiresAt) > time() ? 'active' : 'expired';

                $issModel->insert([
                    'code'          => 'VC-' . random_int(100000, 999999),
                    'voucher_id'    => $vouId,
                    'location_id'   => $locId,
                    'duration'      => $duration,
                    'guest_name'    => 'Guest user',
                    'guest_voucher' => $user,
                    'issued_by'     => $adminId,
                    'issued_at'     => $issuedAt,
                    'expires_at'    => $expiresAt,
                    'status'        => $status,
                ]);
            }
        }
    }
}
