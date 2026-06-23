<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * VoucherModel — ตาราง vouchers (voucher จริงรายใบในคลัง)
 */
class VoucherModel extends Model
{
    protected $table         = 'vouchers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['lot_id', 'location_id', 'duration', 'vou_username', 'vou_password', 'status'];

    // นับ voucher คงเหลือ (instock) แยกตาม location
    public function remainingByLocation(): array
    {
        return $this->select('location_id, COUNT(*) AS qty')
            ->where('status', 'instock')
            ->groupBy('location_id')
            ->get()->getResultArray();
    }

    // หยิบ voucher ว่าง 1 ใบ ตาม location + duration (ใช้ตอน issue)
    public function pickAvailable(int $locationId, string $duration): ?array
    {
        return $this->where('location_id', $locationId)
            ->where('duration', $duration)
            ->where('status', 'instock')
            ->orderBy('id', 'ASC')
            ->first();
    }
}
