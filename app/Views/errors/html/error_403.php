<?php

/**
 * หน้า 403 Forbidden — ดีไซน์ minimal (ตัวเลขใหญ่ไล่สีโทน danger)
 * standalone page: โหลด url helper เอง (base_url/site_url ต้องใช้ได้)
 */
helper('url');
$locale = service('request')->getLocale();
$isEn   = $locale === 'en';
$sub    = $isEn ? 'Access denied' : 'ไม่มีสิทธิ์เข้าถึง';
$text   = $isEn
    ? "You don't have permission to access this page. Contact an administrator if you think this is a mistake."
    : 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ หากคิดว่าผิดพลาด โปรดติดต่อผู้ดูแลระบบ';
$btn    = $isEn ? 'Back to home' : 'กลับหน้าหลัก';
?>
<!DOCTYPE html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 · NetPass</title>
    <link href="<?= base_url('assets/fonts/fonts.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/error.css') ?>" rel="stylesheet">
</head>
<body class="np-err-body is-danger">
    <main class="np-err">
        <!-- icon minimal: ล็อก (ไม่มีสิทธิ์) -->
        <div class="np-err-icon">
            <svg viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>

        <h1 class="np-err-num">403</h1>
        <h2 class="np-err-sub"><?= esc($sub) ?></h2>
        <p class="np-err-text"><?= esc($text) ?></p>
        <a class="np-err-link" href="<?= site_url('/') ?>"><?= esc($btn) ?></a>
    </main>
</body>
</html>
