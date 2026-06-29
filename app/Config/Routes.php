<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// Shield: login / logout / register ฯลฯ — override login ให้ใช้ username; ยกเว้น register/magic-link (ปิดใช้งาน)
service('auth')->routes($routes, ['except' => ['login', 'logout', 'register', 'magic-link']]);
$routes->get('login', '\App\Controllers\Auth\LoginController::loginView');
$routes->post('login', '\App\Controllers\Auth\LoginController::loginAction');
$routes->get('logout', '\App\Controllers\Auth\LoginController::logoutAction');   // override: logout โดยไม่ขึ้นข้อความ flash

// สลับภาษา (ใช้ได้ทั้ง guest/หลัง login)
$routes->get('lang/(:segment)', 'LanguageController::switch/$1');

// ---------------------------------------------------------------------
// หน้าผู้ใช้ (ต้อง login) — URL: /...
// ---------------------------------------------------------------------
// บังคับเปลี่ยนรหัสผ่านครั้งแรก (ต้อง login ก่อน)
$routes->group('', ['filter' => 'session'], static function ($routes) {
    $routes->get('force-password', 'ForcePasswordController::index');
    $routes->post('force-password', 'ForcePasswordController::update');
});

$routes->group('', ['filter' => 'session'], static function ($routes) {
    $routes->get('myvoucher', 'User\MyVoucherController::index');
    $routes->get('myvoucher/data', 'User\MyVoucherController::data');       // DataTables server-side (JSON)
    $routes->get('myvoucher/tickets', 'User\MyVoucherController::tickets'); // ข้อมูลตั๋วตาม id (พิมพ์หลายใบ)
    $routes->post('voucher/request', 'VoucherRequestController::request');
    $routes->get('profile', 'ProfileController::index');
    $routes->post('profile', 'ProfileController::update');
    $routes->post('profile/password', 'ProfileController::changePassword');
});

// ---------------------------------------------------------------------
// หลังบ้าน (เฉพาะกลุ่ม admin) — URL: /admin/...
// ---------------------------------------------------------------------
$routes->group('admin', ['filter' => 'group:admin'], static function ($routes) {
    $routes->get('/', 'Admin\DashboardController::index');

    // โปรไฟล์ admin
    $routes->get('profile', 'ProfileController::index');
    $routes->post('profile', 'ProfileController::update');
    $routes->post('profile/password', 'ProfileController::changePassword');

    // ประวัติการออก voucher ทั้งหมด
    $routes->get('voucher', 'Admin\VoucherController::index');
    $routes->get('voucher/data', 'Admin\VoucherController::data');   // DataTables server-side (JSON)
    $routes->get('voucher/tickets', 'Admin\VoucherController::tickets');   // ข้อมูลตั๋วตาม id (พิมพ์หลายใบ)

    // คลัง voucher แยกตาม location
    $routes->get('pool', 'Admin\PoolController::index');
    $routes->get('pool/data', 'Admin\PoolController::poolData');                    // DataTables server-side (สรุปแยกพื้นที่)
    $routes->get('pool/location/(:num)', 'Admin\PoolController::detail/$1');
    $routes->get('pool/location/(:num)/data', 'Admin\PoolController::detailData/$1');  // DataTables server-side
    $routes->get('pool/import/template', 'Admin\PoolController::importTemplate');
    $routes->post('pool/import', 'Admin\PoolController::import');
    $routes->post('pool/voucher', 'Admin\PoolController::addVoucher');
    $routes->post('pool/voucher/(:num)/update', 'Admin\PoolController::updateVoucher/$1');
    $routes->post('pool/voucher/(:num)/delete', 'Admin\PoolController::deleteVoucher/$1');

    // พื้นที่ (location) — จัดการในหน้า Voucher Pool (ไม่มีหน้า list แยก) เหลือเฉพาะ action
    $routes->post('locations', 'Admin\LocationController::create');
    $routes->post('locations/(:num)/update', 'Admin\LocationController::update/$1');
    $routes->post('locations/(:num)/delete', 'Admin\LocationController::delete/$1');

    // สมาชิก — จัดการ user/admin
    $routes->get('members', 'Admin\MemberController::index');
    $routes->get('members/data', 'Admin\MemberController::data');   // DataTables server-side
    $routes->post('members', 'Admin\MemberController::create');
    $routes->post('members/(:num)/update', 'Admin\MemberController::update/$1');
    $routes->post('members/(:num)/toggle', 'Admin\MemberController::toggle/$1');
    $routes->post('members/(:num)/delete', 'Admin\MemberController::delete/$1');
    $routes->post('members/(:num)/reset-password', 'Admin\MemberController::resetPassword/$1');
    $routes->get('members/import/template', 'Admin\MemberController::importTemplate');
    $routes->post('members/import', 'Admin\MemberController::import');

    // บันทึกการใช้งาน (audit log)
    $routes->get('logs', 'Admin\ActivityLogController::index');
    $routes->get('logs/data', 'Admin\ActivityLogController::data');   // DataTables server-side (JSON)
    $routes->get('logs/export', 'Admin\ActivityLogController::export');   // ดาวน์โหลด CSV (Excel)
});
