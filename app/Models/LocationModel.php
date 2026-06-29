<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * LocationModel — ตาราง locations (พื้นที่/SSID)
 */
class LocationModel extends Model
{
    protected $table         = 'locations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'name_en', 'ssid'];

    // EN เป็นชื่อหลัก (บังคับ), TH เป็นตัวเลือก — ให้ตรงกับ validation ใน LocationController
    protected $validationRules = [
        'name_en' => 'required|max_length[150]',
        'name'    => 'permit_empty|max_length[150]',
        'ssid'    => 'required|max_length[100]',
    ];
}
