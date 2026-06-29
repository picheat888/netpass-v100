<?php

// บันทึกการใช้งาน (Activity Log / Audit) — อังกฤษ
return [
    'title'    => 'Activity Log',
    'subtitle' => 'Full history of actions in the system for auditing',

    // ตัวกรอง
    'filterAllActions' => 'All actions',
    'dateFrom'         => 'From',
    'dateTo'           => 'To',

    // หัวคอลัมน์
    'colTime'   => 'Time',
    'colActor'  => 'User',
    'colAction' => 'Action',
    'colTarget' => 'Item',
    'colIp'     => 'IP',
    'colDetail' => 'Details',
    'colRole'   => 'Role',
    'colType'   => 'Type',
    'view'      => 'View',
    'system'    => 'System',
    'export'    => 'Export CSV',
    'exportNeedPeriod' => 'Please select a date range',

    // modal รายละเอียด
    'detailTitle' => 'Action details',
    'detailSub'   => 'Information for this event',
    'noDetails'   => 'No additional details',
    'guestList'   => 'Voucher recipients',
    'colName'     => 'Name',
    'colPhone'    => 'Phone',
    'colUsername' => 'Username',

    // ชื่อ action
    'act' => [
        'auth.login'            => 'Sign in',
        'auth.logout'           => 'Sign out',
        'auth.password_change'  => 'Change password',
        'voucher.request'       => 'Request voucher',
        'voucher.import'        => 'Import vouchers',
        'voucher.add'           => 'Add voucher',
        'voucher.update'        => 'Edit voucher',
        'voucher.delete'        => 'Delete voucher',
        'location.create'       => 'Create location',
        'location.update'       => 'Edit location',
        'location.delete'       => 'Delete location',
        'member.create'         => 'Create member',
        'member.update'         => 'Edit member',
        'member.delete'         => 'Delete member',
        'member.toggle'         => 'Toggle account',
        'member.reset_password' => 'Reset password',
        'profile.update'        => 'Edit profile',
    ],

    // ประเภทรายการ
    'tgt' => [
        'voucher'  => 'Voucher',
        'location' => 'Location',
        'member'   => 'Member',
        'request'  => 'Request',
    ],
];
