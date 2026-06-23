<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVouchers extends Migration
{
    // สร้างตาราง vouchers (voucher จริงรายใบในคลัง — login ของ guest)
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'lot_id'       => ['type' => 'BIGINT', 'unsigned' => true],   // FK -> voucher_lot
            'location_id'  => ['type' => 'BIGINT', 'unsigned' => true],   // FK -> locations (denormalize จาก lot)
            'duration'     => ['type' => 'VARCHAR', 'constraint' => 10],  // config key (denormalize จาก lot)
            'vou_username' => ['type' => 'VARCHAR', 'constraint' => 50],  // guest-xxxx
            'vou_password' => ['type' => 'VARCHAR', 'constraint' => 50],  // รหัส voucher (plaintext โดยตั้งใจ)
            'status'       => ['type' => 'ENUM', 'constraint' => ['instock', 'issued'], 'default' => 'instock'],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // index หา voucher ว่างตอน issue (location + duration + status)
        $this->forge->addKey(['location_id', 'duration', 'status']);
        $this->forge->addForeignKey('lot_id', 'voucher_lot', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('location_id', 'locations', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('vouchers');
    }

    public function down()
    {
        $this->forge->dropTable('vouchers');
    }
}
