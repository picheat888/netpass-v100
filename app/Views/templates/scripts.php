<?php

/**
 * Scripts ส่วนกลาง — Bootstrap bundle + app.js
 */
?>
<script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/js/dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/js/dataTables.bootstrap5.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/tom-select/js/tom-select.complete.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/qrcode/qrcodejs.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/html-to-image/html-to-image.min.js') ?>"></script>
<?php // cache-busting: ผูกเวอร์ชันกับเวลาที่แก้ไฟล์ → browser ดึงตัวใหม่ทันทีที่โค้ดเปลี่ยน
$appJs = FCPATH . 'assets/js/app.js';
$appVer = is_file($appJs) ? filemtime($appJs) : '1'; ?>
<script src="<?= base_url('assets/js/app.js') ?>?v=<?= $appVer ?>"></script>
