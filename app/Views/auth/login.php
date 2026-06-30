<?php

/**
 * หน้า Login (custom theme NetPass) — ใช้แทน view ของ Shield
 * form post ไป /login (Shield::loginAction) field: username, password, remember
 * error แสดงแบบ inline ใต้ช่อง: validation รายช่อง + auth-fail ลงใต้ password
 */
$locale  = service('request')->getLocale();     // ภาษาปัจจุบัน (หน้านี้ไม่มี $locale ส่งจาก controller)
$errors  = (array) (session('errors') ?? []);   // validation รายช่อง (keyed username/password)
$authErr = session('error');                    // auth ล้มเหลว — ข้อความเดียว ไม่ระบุ field

// คืน class ' np-invalid' (ขอบแดง) ถ้าช่องนั้นมี error
$invalid = static fn (string $field, bool $extra = false) => (isset($errors[$field]) || $extra) ? ' np-invalid' : '';
// คืน <p> ข้อความ error inline ใต้ช่อง (ถ้ามีข้อความ)
$fieldErr = static fn (?string $msg) => ! empty($msg)
    ? '<p class="np-field-err"><i class="bi bi-info-circle-fill"></i> ' . esc($msg) . '</p>' : '';
?>
<!DOCTYPE html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= lang('Common.login') ?> · NetPass</title>
    <link href="<?= base_url('assets/fonts/fonts.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/plugins/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/plugins/bootstrap-icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="np-login-body">

    <div class="np-login-card np-card">
        <!-- สลับภาษา (reuse สไตล์เดียวกับ topbar) -->
        <div class="np-login-lang dropdown">
            <button class="np-pill-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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

        <div class="text-center mb-4">
            <div class="np-brand-logo mx-auto mb-3" style="width:54px;height:54px"><img src="<?= base_url('logo-1.png') ?>" alt="NetPass"></div>
            <h1 class="h4 fw-bold mb-1">NetPass</h1>
            <div class="text-muted" style="font-size:13px">Guest Wi-Fi Voucher System</div>
        </div>

        <?php if (session('message') !== null): ?>
            <div class="alert alert-success py-2 small"><?= esc(session('message')) ?></div>
        <?php endif; ?>

        <form action="<?= url_to('login') ?>" method="post" id="loginForm" novalidate>
            <?= csrf_field() ?>

            <div class="np-field">
                <label class="form-label small fw-semibold"><?= lang('Common.username') ?></label>
                <input type="text" name="username" autocomplete="username"
                       class="form-control<?= $invalid('username', ! empty($authErr)) ?>" value="<?= old('username') ?>" required autofocus>
                <?= $fieldErr($errors['username'] ?? null) ?>
            </div>

            <div class="np-field">
                <label class="form-label small fw-semibold"><?= lang('Common.password') ?></label>
                <div class="np-pwd-wrap">
                    <input type="password" name="password" id="loginPwd" autocomplete="current-password"
                           class="form-control<?= $invalid('password', ! empty($authErr)) ?>" required>
                    <button type="button" class="np-pwd-toggle" tabindex="-1" aria-label="<?= esc(lang('Profile.togglePwd'), 'attr') ?>"><i class="bi bi-eye"></i></button>
                </div>
                <?= $fieldErr($errors['password'] ?? ($authErr !== null ? lang('Common.invalidLogin') : null)) ?>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" name="remember" class="form-check-input" id="remember" <?= old('remember') ? 'checked' : '' ?>>
                <label class="form-check-label small" for="remember"><?= lang('Common.rememberMe') ?></label>
            </div>

            <button type="submit" id="loginBtn" class="btn btn-np w-100 py-2">
                <span class="np-btn-label"><?= lang('Common.login') ?></span>
                <span class="spinner-border spinner-border-sm text-white d-none" role="status" aria-hidden="true"></span>
            </button>
        </form>
    </div>

    <div class="np-login-foot">© <?= date('Y') ?> NetPass · v1.0</div>

    <script src="<?= base_url('assets/plugins/bootstrap/bootstrap.bundle.min.js') ?>"></script>
    <?php $loginJs = FCPATH . 'assets/js/auth/login.js'; ?>
    <script src="<?= base_url('assets/js/auth/login.js') ?>?v=<?= is_file($loginJs) ? filemtime($loginJs) : '1' ?>"></script>

</body>
</html>
