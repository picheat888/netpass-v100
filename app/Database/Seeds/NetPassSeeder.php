<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

/**
 * NetPassSeeder — ข้อมูลตั้งต้น: บัญชี admin + user ธรรมดา เท่านั้น
 * (บัญชี demo เฉพาะ development — ไม่ควรมีบน production)
 */
class NetPassSeeder extends Seeder
{
    public function run()
    {
        if (ENVIRONMENT !== 'development') {
            return;
        }

        $users = new UserModel();

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
    }
}
