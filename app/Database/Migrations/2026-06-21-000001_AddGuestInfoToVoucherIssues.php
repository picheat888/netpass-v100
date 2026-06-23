<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGuestInfoToVoucherIssues extends Migration
{
    // เพิ่มข้อมูล guest ต่อ voucher (supplier/ชื่อ/นามสกุล/เบอร์) — nullable เพื่อความเข้ากันได้ย้อนหลัง
    public function up()
    {
        $this->forge->addColumn('voucher_issues', [
            'supplier'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'guest_name'],
            'guest_firstname' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'supplier'],
            'guest_lastname'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'guest_firstname'],
            'guest_phone'     => ['type' => 'VARCHAR', 'constraint' => 30,  'null' => true, 'after' => 'guest_lastname'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('voucher_issues', ['supplier', 'guest_firstname', 'guest_lastname', 'guest_phone']);
    }
}
