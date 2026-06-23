<?php

/**
 * Sidebar — เมนูซ้าย (แสดงตาม role: admin เห็นครบ, user เห็นแค่ My Voucher)
 * @var string $active  key ของเมนูที่ active
 */
helper('netpass');
$active = $active ?? '';
?>
<aside class="np-sidebar">
    <div class="np-brand">
        <div class="np-brand-logo"><img src="<?= base_url('logo-1.png') ?>" alt="NetPass"></div>
        <div>
            <div class="np-brand-name">NetPass</div>
            <div class="np-brand-sub">Guest Wi-Fi System</div>
        </div>
    </div>

    <div class="np-nav-label"><?= lang('Nav.mainMenu') ?></div>
    <nav class="np-nav">
        <?php if (is_admin()): ?>
            <a class="np-nav-item <?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= site_url('admin') ?>">
                <i class="bi bi-grid-1x2"></i><span class="flex-grow-1"><?= lang('Nav.dashboard') ?></span>
            </a>
            <a class="np-nav-item <?= $active === 'voucher' ? 'active' : '' ?>" href="<?= site_url('admin/voucher') ?>">
                <i class="bi bi-ticket-detailed"></i><span class="flex-grow-1"><?= lang('Nav.voucher') ?></span>
            </a>
            <a class="np-nav-item <?= $active === 'pool' ? 'active' : '' ?>" href="<?= site_url('admin/pool') ?>">
                <i class="bi bi-box-seam"></i><span class="flex-grow-1"><?= lang('Nav.pool') ?></span>
            </a>
            <a class="np-nav-item <?= $active === 'members' ? 'active' : '' ?>" href="<?= site_url('admin/members') ?>">
                <i class="bi bi-people"></i><span class="flex-grow-1"><?= lang('Nav.members') ?></span>
            </a>
        <?php else: ?>
            <a class="np-nav-item <?= $active === 'myvoucher' ? 'active' : '' ?>" href="<?= site_url('myvoucher') ?>">
                <i class="bi bi-ticket-perforated"></i><span class="flex-grow-1"><?= lang('Nav.myvoucher') ?></span>
            </a>
        <?php endif; ?>

    </nav>

    <div class="np-side-foot">© <?= date('Y') ?> NetPass · v1.0</div>
</aside>
