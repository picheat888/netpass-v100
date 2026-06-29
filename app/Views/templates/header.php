<?php

/**
 * ส่วนหัว HTML — โหลดฟอนต์ + Bootstrap 5 + style.css
 * @var string $title  หัวข้อหน้า (จาก controller)
 */
?>
<!DOCTYPE html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'NetPass') ?> · NetPass</title>

    <!-- ฟอนต์หลักของระบบ (self-hosted): Poppins (ละติน) + Prompt (ไทย) + Google Sans Code (mono) -->
    <link href="<?= base_url('assets/fonts/fonts.css') ?>" rel="stylesheet">

    <link href="<?= base_url('assets/plugins/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/plugins/bootstrap-icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/plugins/datatables/css/dataTables.bootstrap5.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/plugins/tom-select/css/tom-select.bootstrap5.min.css') ?>" rel="stylesheet">
    <?php // cache-busting: ผูกเวอร์ชันกับเวลาที่แก้ไฟล์ → browser โหลด CSS ใหม่ทันทีที่เปลี่ยน
    $cssFile = FCPATH . 'assets/css/style.css'; $cssVer = is_file($cssFile) ? filemtime($cssFile) : '1';
    $dlgFile = FCPATH . 'assets/css/dialog.css'; $dlgVer = is_file($dlgFile) ? filemtime($dlgFile) : '1'; ?>
    <link href="<?= base_url('assets/css/style.css') ?>?v=<?= $cssVer ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/dialog.css') ?>?v=<?= $dlgVer ?>" rel="stylesheet">
</head>
<body>
