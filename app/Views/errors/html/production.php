<?php

/**
 * หน้า error ทั่วไปบน production — ดีไซน์ minimal (ไม่โชว์เลข code → ไอคอนใหญ่)
 * CI4 ใช้ไฟล์นี้เป็น catch-all เมื่อเกิด exception ที่ไม่มี view เฉพาะ (ส่วนใหญ่คือ 500)
 * standalone page: โหลด url helper เอง, ไม่โชว์รายละเอียด exception (กัน info-leak)
 */
helper('url');
$locale = service('request')->getLocale();
$isEn   = $locale === 'en';
$sub    = $isEn ? 'Something went wrong' : 'ขออภัย เกิดข้อผิดพลาด';
$text   = $isEn
    ? 'An unexpected error occurred. Please try again later.'
    : 'เกิดข้อผิดพลาดบางอย่าง โปรดลองใหม่อีกครั้งภายหลัง';
$btn    = $isEn ? 'Back to home' : 'กลับหน้าหลัก';
?>
<!DOCTYPE html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Error · NetPass</title>
    <link href="<?= base_url('assets/fonts/fonts.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/error.css') ?>" rel="stylesheet">
</head>
<body class="np-err-body is-danger">
    <main class="np-err">
        <!-- catch-all: ไม่โชว์เลข/รายละเอียด -->
        <div class="np-err-glyph">
            <svg viewBox="0 0 24 24"><path d="m2 2 20 20"/><path d="M5.782 5.782A7 7 0 0 0 9 19h8.5a4.5 4.5 0 0 0 1.307-.193"/><path d="M21.532 16.5A4.5 4.5 0 0 0 17.5 10h-1.79A7.008 7.008 0 0 0 10 5.07"/></svg>
        </div>

        <h2 class="np-err-sub"><?= esc($sub) ?></h2>
        <p class="np-err-text"><?= esc($text) ?></p>
        <a class="np-err-link" href="<?= site_url('/') ?>"><?= esc($btn) ?></a>
    </main>
</body>
</html>
