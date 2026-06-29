<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * เปลี่ยนรูปแบบ code ของ voucher_lot เดิม → LOT-yyyy-xxxx
 * (yyyy = ปีจาก created_at, xxxx = ลำดับต่อปี รีเซ็ตทุกปี 4 หลัก)
 * PK (id) คงเดิม — ไม่กระทบ FK vouchers.lot_id
 */
class UpdateVoucherLotCodeFormat extends Migration
{
    public function up()
    {
        $rows = $this->db->table('voucher_lot')
            ->select('id, created_at')
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $seqByYear = [];   // ['2026' => 3, ...] ตัวนับลำดับต่อปี
        foreach ($rows as $r) {
            $year = ! empty($r['created_at']) ? date('Y', strtotime($r['created_at'])) : date('Y');
            $seqByYear[$year] = ($seqByYear[$year] ?? 0) + 1;
            $code = 'LOT-' . $year . '-' . str_pad((string) $seqByYear[$year], 4, '0', STR_PAD_LEFT);
            $this->db->table('voucher_lot')->where('id', $r['id'])->update(['code' => $code]);
        }
    }

    public function down()
    {
        // เปลี่ยนรูปแบบข้อมูล — ย้อนกลับ code เดิมไม่ได้แบบ deterministic (no-op)
    }
}
