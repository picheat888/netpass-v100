<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVoucherIssues extends Migration
{
    // สร้างตาราง voucher_issues (ประวัติการออก/ขอใช้ voucher — Voucher + My Voucher)
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code'          => ['type' => 'VARCHAR', 'constraint' => 20],                 // เช่น VC-481203
            'voucher_id'    => ['type' => 'BIGINT', 'unsigned' => true],                  // FK -> vouchers (ใบที่ถูกออก)
            'location_id'   => ['type' => 'BIGINT', 'unsigned' => true],                  // FK -> locations
            'duration'      => ['type' => 'VARCHAR', 'constraint' => 10],                 // config key (snapshot)
            'guest_name'    => ['type' => 'VARCHAR', 'constraint' => 150, 'default' => 'Guest user'], // ชื่อผู้รับ
            'guest_voucher' => ['type' => 'VARCHAR', 'constraint' => 50],                 // snapshot username/password ณ ตอนออก
            'issued_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true], // FK -> users (Shield, int unsigned)
            'issued_at'     => ['type' => 'DATETIME', 'null' => true],                    // เวลาออก
            'expires_at'    => ['type' => 'DATETIME', 'null' => true],                    // = issued_at + duration.hours
            'status'        => ['type' => 'ENUM', 'constraint' => ['active', 'expired'], 'default' => 'active'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        // index filter สถานะ/หมดอายุ
        $this->forge->addKey(['status', 'expires_at']);
        $this->forge->addForeignKey('voucher_id', 'vouchers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('location_id', 'locations', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('issued_by', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('voucher_issues');
    }

    public function down()
    {
        $this->forge->dropTable('voucher_issues');
    }
}
