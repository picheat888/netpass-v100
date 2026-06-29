<?php

/**
 * หน้า 400 Bad Request — ดีไซน์ minimal (ตัวเลขใหญ่ไล่สีโทน warning)
 * standalone page: โหลด url helper เอง (base_url/site_url ต้องใช้ได้)
 */
helper('url');
$locale = service('request')->getLocale();
$isEn   = $locale === 'en';
$sub    = $isEn ? 'Bad request' : 'คำขอไม่ถูกต้อง';
$text   = $isEn
    ? "The request couldn't be understood. Check the address and try again."
    : 'ระบบไม่เข้าใจคำขอนี้ โปรดตรวจสอบ URL แล้วลองใหม่อีกครั้ง';
$btn    = $isEn ? 'Back to home' : 'กลับหน้าหลัก';
?>
<!DOCTYPE html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>400 · NetPass</title>
    <link href="<?= base_url('assets/fonts/fonts.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/error.css') ?>" rel="stylesheet">
</head>
<body class="np-err-body is-warning">
    <main class="np-err">
        <!-- icon minimal: คำขอผิดพลาด -->
        <div class="np-err-icon">
            <svg viewBox="0 0 24 24"><path d="M12 16h.01"/><path d="M12 8v4"/><path d="M15.312 2a2 2 0 0 1 1.414.586l4.688 4.688A2 2 0 0 1 22 8.688v6.624a2 2 0 0 1-.586 1.414l-4.688 4.688a2 2 0 0 1-1.414.586H8.688a2 2 0 0 1-1.414-.586l-4.688-4.688A2 2 0 0 1 2 15.312V8.688a2 2 0 0 1 .586-1.414l4.688-4.688A2 2 0 0 1 8.688 2z"/></svg>
        </div>

        <h1 class="np-err-num">400</h1>
        <h2 class="np-err-sub"><?= esc($sub) ?></h2>
        <p class="np-err-text"><?= esc($text) ?></p>
        <a class="np-err-link" href="<?= site_url('/') ?>"><?= esc($btn) ?></a>
    </main>
</body>
</html>
