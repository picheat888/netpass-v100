<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * DemoCredsSeeder — ปรับ demo accounts ที่มีอยู่ให้เป็น admin/admin และ user/user
 */
class DemoCredsSeeder extends Seeder
{
    public function run()
    {
        // บัญชี demo สร้างเฉพาะ environment development เท่านั้น
        if (ENVIRONMENT !== 'development') {
            CLI::write('ข้าม DemoCredsSeeder: ไม่ใช่ environment development', 'yellow');
            return;
        }

        $users     = new UserModel();
        $passwords = service('passwords');
        $db        = db_connect();

        // admin → รหัส 'admin'
        $admin = $users->where('username', 'admin')->first();
        if ($admin) {
            $db->table('auth_identities')
                ->where('user_id', $admin->id)->where('type', 'email_password')
                ->update(['secret2' => $passwords->hash('admin')]);
        }

        // user1 → เปลี่ยนชื่อเป็น 'user' + รหัส 'user'
        $user = $users->where('username', 'user1')->first() ?? $users->where('username', 'user')->first();
        if ($user) {
            $db->table('users')->where('id', $user->id)->update(['username' => 'user']);
            $db->table('auth_identities')
                ->where('user_id', $user->id)->where('type', 'email_password')
                ->update([
                    'secret'  => 'user@netpass.local',
                    'secret2' => $passwords->hash('user'),
                ]);
        }
    }
}
