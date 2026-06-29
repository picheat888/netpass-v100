<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ตาราง activity_logs — บันทึกการกระทำทั้งระบบ (audit trail)
 * append-only + denormalize เป็น text → ลบ user/location/voucher แล้ว log ยังอยู่
 * จงใจไม่ใส่ FK กับ actor_id/target_id เพื่อให้ log อยู่รอดหลังลบข้อมูลต้นทาง
 */
class CreateActivityLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'action'         => ['type' => 'VARCHAR', 'constraint' => 40],            // เช่น auth.login, voucher.request
            'actor_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],  // users.id (soft ref)
            'actor_name'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],  // ชื่อ snapshot
            'actor_username' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'actor_role'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'target_type'    => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],   // voucher/location/member/request
            'target_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],    // id เดิม (อาจ dangle)
            'target_label'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],  // ชื่อ snapshot
            'details'        => ['type' => 'TEXT', 'null' => true],                    // JSON string (guest/before-after ฯลฯ)
            'ip_address'     => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('action');
        $this->forge->addKey(['target_type', 'target_id']);
        $this->forge->addKey('actor_id');
        $this->forge->addKey('created_at');
        // ไม่ใส่ FK โดยตั้งใจ — audit ต้องอยู่รอดแม้ลบ user/location/voucher
        $this->forge->createTable('activity_logs');
    }

    public function down()
    {
        $this->forge->dropTable('activity_logs');
    }
}
