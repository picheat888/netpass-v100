<?php

/**
 * Topbar — ปุ่ม toggle sidebar, หัวข้อหน้า, สลับภาษา, เมนูผู้ใช้
 * @var string $title
 * @var string $subtitle
 */
helper('netpass');
$user    = auth()->user();
$locale  = service('request')->getLocale();
$fullName = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: ($user->username ?? 'User');
?>
<header class="np-topbar">
    <button class="np-icon-btn" id="npSidebarToggle" title="Toggle sidebar"><i class="bi bi-list fs-5"></i></button>

    <!-- breadcrumb: <พื้นที่> › <หน้าปัจจุบัน> -->
    <nav class="np-crumb" aria-label="breadcrumb">
        <a href="<?= site_url(is_admin() ? 'admin' : 'myvoucher') ?>"><?= is_admin() ? 'Admin' : 'User' ?></a>
        <i class="bi bi-chevron-right"></i>
        <span class="cur text-truncate"><?= esc($title ?? 'NetPass') ?></span>
    </nav>

    <div class="flex-grow-1"></div>

    <!-- สลับภาษา -->
    <div class="dropdown">
        <button class="np-pill-btn" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-globe2"></i><span><?= strtoupper($locale) ?></span>
            <i class="bi bi-chevron-down" style="font-size:11px;color:var(--np-muted-3)"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end np-menu">
            <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= site_url('lang/en') ?>">
                <span class="np-flag">EN</span> English <?php if ($locale === 'en'): ?><i class="bi bi-check2 ms-auto text-primary"></i><?php endif; ?>
            </a></li>
            <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= site_url('lang/th') ?>">
                <span class="np-flag">TH</span> ไทย <?php if ($locale === 'th'): ?><i class="bi bi-check2 ms-auto text-primary"></i><?php endif; ?>
            </a></li>
        </ul>
    </div>

    <div class="np-topbar-divider"></div>

    <!-- เมนูผู้ใช้ -->
    <div class="dropdown">
        <div class="np-user-trigger" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="np-avatar">
                <?php if (! empty($user->img)): ?>
                    <img src="<?= base_url($user->img) ?>" alt="">
                <?php else: ?>
                    <?= esc(mb_strtoupper(mb_substr($fullName, 0, 1))) ?>
                <?php endif ?>
            </div>
            <div class="d-none d-md-block lh-sm">
                <div class="np-user-name text-truncate" style="max-width:150px"><?= esc($fullName) ?></div>
                <div class="np-user-role"><?= is_admin() ? 'Admin' : 'User' ?></div>
            </div>
            <i class="bi bi-chevron-down" style="font-size:13px;color:var(--np-muted-3)"></i>
        </div>
        <ul class="dropdown-menu dropdown-menu-end np-menu">
            <li class="np-menu-head">
                <div class="np-avatar" style="width:36px;height:36px;font-size:13px">
                    <?php if (! empty($user->img)): ?>
                        <img src="<?= base_url($user->img) ?>" alt="">
                    <?php else: ?>
                        <?= esc(mb_strtoupper(mb_substr($fullName, 0, 1))) ?>
                    <?php endif ?>
                </div>
                <div class="min-w-0">
                    <div class="np-user-name text-truncate"><?= esc($fullName) ?></div>
                    <div class="np-user-role text-truncate"><?= esc($user->email ?? '') ?></div>
                </div>
            </li>
            <li><a class="dropdown-item" href="<?= site_url(is_admin() ? 'admin/profile' : 'profile') ?>"><i class="bi bi-person"></i> <?= lang('Nav.profile') ?></a></li>
            <li><a class="dropdown-item text-danger" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right"></i> <?= lang('Nav.signOut') ?></a></li>
        </ul>
    </div>
</header>
