<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserProfileFields extends Migration
{
    // เพิ่มคอลัมน์โปรไฟล์ลงในตาราง users ของ Shield
    // (email/username/password = Shield จัดการ, role = Shield group, active = สถานะ active/inactive)
    public function up()
    {
        $this->forge->addColumn('users', [
            'firstname' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'username'],  // ชื่อ
            'lastname'  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'firstname'], // สกุล
            'position'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'lastname'],  // ตำแหน่ง
            'img'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'position'],  // รูปโปรไฟล์
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['firstname', 'lastname', 'position', 'img']);
    }
}
