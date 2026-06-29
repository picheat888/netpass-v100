<?php

// บันทึกการใช้งาน (Activity Log / Audit) — ไทย
return [
    'title'    => 'บันทึกการใช้งาน',
    'subtitle' => 'ประวัติการกระทำทั้งหมดในระบบ ตรวจสอบย้อนหลังได้',

    // ตัวกรอง
    'filterAllActions' => 'ทุกการกระทำ',
    'dateFrom'         => 'จากวันที่',
    'dateTo'           => 'ถึงวันที่',

    // หัวคอลัมน์
    'colTime'   => 'เวลา',
    'colActor'  => 'ผู้ใช้',
    'colAction' => 'การกระทำ',
    'colTarget' => 'รายการ',
    'colIp'     => 'IP',
    'colDetail' => 'รายละเอียด',
    'colRole'   => 'สิทธิ์',
    'colType'   => 'ประเภท',
    'view'      => 'ดู',
    'system'    => 'ระบบ',
    'export'    => 'ส่งออก CSV',
    'exportNeedPeriod' => 'กรุณาเลือกช่วงวันที่',

    // modal รายละเอียด
    'detailTitle' => 'รายละเอียดการกระทำ',
    'detailSub'   => 'ข้อมูลของ event นี้',
    'noDetails'   => 'ไม่มีรายละเอียดเพิ่มเติม',
    'guestList'   => 'รายชื่อผู้รับ voucher',
    'colName'     => 'ชื่อ',
    'colPhone'    => 'เบอร์โทร',
    'colUsername' => 'Username',

    // ชื่อ action
    'act' => [
        'auth.login'            => 'เข้าสู่ระบบ',
        'auth.logout'           => 'ออกจากระบบ',
        'auth.password_change'  => 'เปลี่ยนรหัสผ่าน',
        'voucher.request'       => 'ขอ Voucher',
        'voucher.import'        => 'นำเข้า Voucher',
        'voucher.add'           => 'เพิ่ม Voucher',
        'voucher.update'        => 'แก้ไข Voucher',
        'voucher.delete'        => 'ลบ Voucher',
        'location.create'       => 'เพิ่มพื้นที่',
        'location.update'       => 'แก้ไขพื้นที่',
        'location.delete'       => 'ลบพื้นที่',
        'member.create'         => 'เพิ่มสมาชิก',
        'member.update'         => 'แก้ไขสมาชิก',
        'member.delete'         => 'ลบสมาชิก',
        'member.toggle'         => 'เปิด/ปิดบัญชี',
        'member.reset_password' => 'รีเซ็ตรหัสผ่าน',
        'profile.update'        => 'แก้ไขโปรไฟล์',
    ],

    // ประเภทรายการ
    'tgt' => [
        'voucher'  => 'Voucher',
        'location' => 'พื้นที่',
        'member'   => 'สมาชิก',
        'request'  => 'คำขอ',
    ],
];
