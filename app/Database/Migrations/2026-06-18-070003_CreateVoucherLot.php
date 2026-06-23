<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVoucherLot extends Migration
{
    // สร้างตาราง voucher_lot (ล็อต voucher ที่ import เข้าคลัง แบ่งตาม location)
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code'        => ['type' => 'VARCHAR', 'constraint' => 30],   // รหัสล็อต เช่น LOT-2401
            'location_id' => ['type' => 'BIGINT', 'unsigned' => true],    // FK -> locations
            'duration'    => ['type' => 'VARCHAR', 'constraint' => 10],   // คีย์ config: 1d/3d/5d/7d
            'qty'         => ['type' => 'INT', 'constraint' => 11],       // จำนวน voucher ในล็อต
            'status'      => ['type' => 'ENUM', 'constraint' => ['active', 'low', 'depleted'], 'default' => 'active'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');                                   // code ห้ามซ้ำ
        $this->forge->addForeignKey('location_id', 'locations', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('voucher_lot');
    }

    public function down()
    {
        $this->forge->dropTable('voucher_lot');
    }
}
