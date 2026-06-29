<?php

/**
 * หน้า 500 Server Error — ดีไซน์ minimal (ตัวเลขใหญ่ไล่สีโทน danger)
 * standalone page: โหลด url helper เอง (base_url/site_url ต้องใช้ได้)
 * ไม่โชว์รายละเอียด exception (กัน info-leak)
 */
helper('url');
$locale = service('request')->getLocale();
$isEn   = $locale === 'en';
$sub    = $isEn ? 'Something went wrong' : 'เกิดข้อผิดพลาด';
$text   = $isEn
    ? 'An internal error occurred. Please try again later.'
    : 'เกิดข้อผิดพลาดภายในระบบ โปรดลองใหม่อีกครั้งภายหลัง';
$btn    = $isEn ? 'Back to home' : 'กลับหน้าหลัก';
?>
<!DOCTYPE html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 · NetPass</title>
    <link href="<?= base_url('assets/fonts/fonts.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/error.css') ?>" rel="stylesheet">
</head>
<body class="np-err-body is-danger">
    <main class="np-err">
        <!-- icon minimal: ข้อผิดพลาดระบบ -->
        <div class="np-err-icon">
            <svg viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
        </div>

        <h1 class="np-err-num">500</h1>
        <h2 class="np-err-sub"><?= esc($sub) ?></h2>
        <p class="np-err-text"><?= esc($text) ?></p>
        <a class="np-err-link" href="<?= site_url('/') ?>"><?= esc($btn) ?></a>
    </main>
</body>
</html>
