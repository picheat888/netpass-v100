<?php

/**
 * Layout หน้าผู้ใช้ (user) — โครงเดียวกับ admin, sidebar แสดงเมนูตาม role อัตโนมัติ
 */
?>
<?= $this->include('templates/header') ?>
<div class="np-shell">
    <?= $this->include('templates/sidebar') ?>
    <div class="np-backdrop"></div>
    <div class="np-main">
        <?= $this->include('templates/topbar') ?>
        <main class="np-content">
            <div class="np-content-inner">
                <!-- หัวข้อหน้า -->
                <div class="np-page-head">
                    <h1 class="np-page-h1"><?= esc($title ?? 'NetPass') ?></h1>
                    <?php if (! empty($subtitle)): ?>
                        <p class="np-page-desc"><?= esc($subtitle) ?></p>
                    <?php endif; ?>
                </div>
                <?php /* ข้อความสำเร็จ/ผิดพลาด (message/error) แสดงเป็น Toast */ ?>
                <?php if (session('errors')): ?>
                    <div class="alert alert-danger alert-dismissible py-2 mb-3" role="alert">
                        <ul class="mb-0 ps-3">
                            <?php foreach ((array) session('errors') as $err): ?>
                                <li><?= esc($err) ?></li>
                            <?php endforeach ?>
                        </ul>
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif ?>
                <?= $this->renderSection('content') ?>
            </div>
        </main>
    </div>
</div>

<?= $this->renderSection('modals') ?>
<?= $this->include('templates/request_modal') ?>
<?= $this->include('templates/scripts') ?>
<?= $this->include('templates/toast') ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>
