<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * VoucherLotModel — ตาราง voucher_lot (ล็อตในคลัง)
 */
class VoucherLotModel extends Model
{
    protected $table         = 'voucher_lot';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['code', 'location_id', 'duration', 'qty', 'status'];
}
