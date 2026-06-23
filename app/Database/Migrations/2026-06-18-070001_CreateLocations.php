<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLocations extends Migration
{
    // สร้างตาราง locations (พื้นที่/โซน + SSID ของ Wi-Fi)
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],                 // ชื่อพื้นที่ (ไทย)
            'name_en'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true], // ชื่อพื้นที่ (อังกฤษ)
            'ssid'       => ['type' => 'VARCHAR', 'constraint' => 100],                 // ชื่อ Wi-Fi
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('locations');
    }

    public function down()
    {
        $this->forge->dropTable('locations');
    }
}
