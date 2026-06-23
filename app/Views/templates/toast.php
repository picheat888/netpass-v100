<?php
/**
 * Toast แจ้งผล (success/error) — มุมขวาล่าง, แสดงเองแล้วซ่อนอัตโนมัติ
 * อ่าน flashdata: message / pwd_message (สำเร็จ) และ error (ผิดพลาด)
 * ใช้แทนกล่อง alert ด้านบนสำหรับข้อความแจ้งผลการบันทึก
 */
$toasts = [];
if (session('message'))        { $toasts[] = ['type' => 'ok',     'msg' => session('message')]; }
if (session('pwd_message'))    { $toasts[] = ['type' => 'ok',     'msg' => session('pwd_message')]; }
if (session('message_danger')) { $toasts[] = ['type' => 'danger', 'msg' => session('message_danger')]; }
if (session('error'))          { $toasts[] = ['type' => 'err',    'msg' => session('error')]; }

// แมป type → class สี + ไอคอน (danger = สำเร็จแต่เป็นการลบ → แดง+ถังขยะ)
$toastCls  = ['ok' => 'np-toast-ok', 'err' => 'np-toast-err', 'danger' => 'np-toast-err'];
$toastIcon = ['ok' => 'bi-check-circle-fill', 'err' => 'bi-exclamation-circle-fill', 'danger' => 'bi-trash3-fill'];
?>
<?php if ($toasts): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3 np-toast-wrap">
    <?php foreach ($toasts as $toast): ?>
        <div class="toast np-toast <?= $toastCls[$toast['type']] ?>" role="alert" aria-live="polite" aria-atomic="true">
            <div class="toast-body">
                <i class="bi <?= $toastIcon[$toast['type']] ?>"></i>
                <span><?= esc($toast['msg']) ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php endforeach ?>
</div>
<script src="<?= base_url('assets/js/toast.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/toast.js') ?>"></script>
<?php endif ?>
