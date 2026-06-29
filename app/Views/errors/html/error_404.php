<?php

/**
 * หน้า 404 Not Found — ดีไซน์ minimal (ตัวเลขใหญ่ไล่สีโทน info)
 * standalone page: โหลด url helper เอง (base_url/site_url ต้องใช้ได้)
 * ข้อความ 2 ภาษาตาม locale, ไม่โชว์รายละเอียด route ที่ไม่พบ (กัน info-leak)
 */
helper('url');
$locale = service('request')->getLocale();
$isEn   = $locale === 'en';
$sub    = $isEn ? 'Page not found' : 'ไม่พบหน้าที่คุณค้นหา';
$text   = $isEn
    ? 'The page may have been moved or removed, or the address was typed incorrectly.'
    : 'หน้านี้อาจถูกย้าย ถูกลบ หรือ URL พิมพ์ไม่ถูกต้อง';
$btn    = $isEn ? 'Back to home' : 'กลับหน้าหลัก';
?>
<!DOCTYPE html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 · NetPass</title>
    <link href="<?= base_url('assets/fonts/fonts.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/error.css') ?>" rel="stylesheet">
</head>
<body class="np-err-body is-info">
    <main class="np-err">
        <!-- icon minimal: สัญญาณหลุด -->
        <div class="np-err-icon">
            <svg viewBox="0 0 24 24"><line x1="2" x2="22" y1="2" y2="22"/><path d="M8.5 16.5a5 5 0 0 1 7 0"/><path d="M2 8.82a15 15 0 0 1 4.17-2.65"/><path d="M10.66 5c4.01-.36 8.14.9 11.34 3.76"/><path d="M16.85 11.25a10 10 0 0 1 2.22 1.68"/><path d="M5 13a10 10 0 0 1 5.24-2.76"/><line x1="12" x2="12.01" y1="20" y2="20"/></svg>
        </div>

        <h1 class="np-err-num">404</h1>
        <h2 class="np-err-sub"><?= esc($sub) ?></h2>
        <p class="np-err-text"><?= esc($text) ?></p>
        <a class="np-err-link" href="<?= site_url('/') ?>"><?= esc($btn) ?></a>
    </main>
</body>
</html>
