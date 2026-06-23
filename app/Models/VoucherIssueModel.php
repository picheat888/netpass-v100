<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * VoucherIssueModel — ตาราง voucher_issues (ประวัติการออก/ขอใช้ voucher)
 */
class VoucherIssueModel extends Model
{
    protected $table         = 'voucher_issues';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'code', 'voucher_id', 'location_id', 'duration',
        'guest_name', 'supplier', 'guest_firstname', 'guest_lastname', 'guest_phone',
        'guest_voucher', 'issued_by', 'issued_at', 'expires_at', 'status',
    ];

    // นับจำนวนที่ออกวันนี้
    public function issuedToday(): int
    {
        return $this->where('DATE(issued_at)', date('Y-m-d'))->countAllResults();
    }
}
