<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLocaleToUsers extends Migration
{
    // เก็บภาษาที่ผู้ใช้เลือกไว้ (th/en) — ค่าว่าง = ยังไม่เคยเลือก ใช้ default ของระบบ (en)
    public function up()
    {
        $this->forge->addColumn('users', [
            'locale' => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true, 'after' => 'img'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'locale');
    }
}
