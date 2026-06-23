<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * ตั้งค่าเกี่ยวกับ Voucher — เก็บ durations ไว้ที่นี่ (ไม่เป็น table)
 */
class Voucher extends BaseConfig
{
    /**
     * ระยะเวลาใช้งาน voucher
     * key (เก็บใน DB คอลัมน์ duration) => รายละเอียด
     */
    public array $durations = [
        '1d' => ['label' => '1 วัน', 'label_en' => '1 Day',   'sub' => 'Day pass',    'hours' => 24],
        '3d' => ['label' => '3 วัน', 'label_en' => '3 Days',  'sub' => 'Short stay',  'hours' => 72],
        '5d' => ['label' => '5 วัน', 'label_en' => '5 Days',  'sub' => 'Extended',    'hours' => 120],
        '7d' => ['label' => '7 วัน', 'label_en' => '7 Days',  'sub' => 'Week',        'hours' => 168],
    ];
}
