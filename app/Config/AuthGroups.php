<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * กลุ่มเริ่มต้นของ user ที่สมัครใหม่
     */
    public string $defaultGroup = 'user';

    /**
     * กลุ่มผู้ใช้งานในระบบ NetPass — มีแค่ admin กับ user
     *
     * @var array<string, array<string, string>>
     */
    public array $groups = [
        'admin' => [
            'title'       => 'Admin',
            'description' => 'ผู้ดูแลระบบ จัดการ voucher/pool/สมาชิก/location',
        ],
        'user' => [
            'title'       => 'User',
            'description' => 'ผู้ใช้งานทั่วไป ขอ voucher และดูประวัติของตัวเอง',
        ],
    ];

    /**
     * สิทธิ์ที่มีในระบบ
     */
    public array $permissions = [
        'admin.access'      => 'เข้าถึงหน้าหลังบ้าน',
        'voucher.manage'    => 'จัดการ voucher และ voucher pool',
        'members.manage'    => 'จัดการสมาชิก',
        'locations.manage'  => 'จัดการ location',
    ];

    /**
     * Matrix — กำหนดว่ากลุ่มไหนมีสิทธิ์อะไร
     */
    public array $matrix = [
        'admin' => [
            'admin.access',
            'voucher.manage',
            'members.manage',
            'locations.manage',
        ],
        'user' => [],
    ];
}
