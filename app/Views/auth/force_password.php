<?php
/**
 * หน้าบังคับเปลี่ยนรหัสผ่านครั้งแรก (standalone — ธีมเดียวกับ login)
 * โพสต์ไป /force-password (ForcePasswordController::update)
 */
$fErrors = (array) (session('force_errors') ?? []);
$invalid = static fn (string $field) => isset($fErrors[$field]) ? ' np-invalid' : '';
$fieldErr = static fn (string $field) => ! empty($fErrors[$field])
    ? '<p class="np-field-err"><i class="bi bi-info-circle-fill"></i> ' . esc($fErrors[$field]) . '</p>' : '';
?>
<!DOCTYPE html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= lang('Force.title') ?> · NetPass</title>
    <link href="<?= base_url('assets/fonts/fonts.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/plugins/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/plugins/bootstrap-icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/dialog.css') ?>" rel="stylesheet">
</head>
<body class="np-login-body">

    <div class="np-login-card np-card np-force-card">
        <div class="text-center mb-4">
            <div class="np-force-ico mx-auto mb-3"><i class="bi bi-shield-lock"></i></div>
            <h1 class="h5 fw-bold mb-1"><?= lang('Force.heading') ?></h1>
            <div class="text-muted" style="font-size:13px; line-height:1.5"><?= lang('Force.subtitle') ?></div>
        </div>

        <form action="<?= site_url('force-password') ?>" method="post">
            <?= csrf_field() ?>

            <div class="np-field">
                <label class="form-label fw-semibold"><?= lang('Force.newPassword') ?> <span class="np-req">*</span></label>
                <div class="np-pwd-wrap">
                    <input type="password" name="new_password" id="fNew" class="form-control<?= $invalid('new_password') ?>" autocomplete="new-password" autofocus>
                    <button type="button" class="np-pwd-toggle" tabindex="-1" aria-label="<?= esc(lang('Profile.togglePwd'), 'attr') ?>"><i class="bi bi-eye"></i></button>
                </div>
                <?= $fieldErr('new_password') ?>
                <div class="np-pwd-meter" id="fMeter">
                    <div class="np-pwd-meter-track"><span class="np-pwd-meter-fill"></span></div>
                    <span class="np-pwd-meter-label" id="fMeterLabel"></span>
                </div>
            </div>

            <div class="np-field">
                <label class="form-label fw-semibold"><?= lang('Force.confirmPassword') ?> <span class="np-req">*</span></label>
                <div class="np-pwd-wrap">
                    <input type="password" name="confirm_password" id="fConfirm" class="form-control<?= $invalid('confirm_password') ?>" autocomplete="new-password">
                    <button type="button" class="np-pwd-toggle" tabindex="-1" aria-label="<?= esc(lang('Profile.togglePwd'), 'attr') ?>"><i class="bi bi-eye"></i></button>
                </div>
                <?= $fieldErr('confirm_password') ?>
                <p class="np-match-msg" id="fMatch"></p>
            </div>

            <ul class="np-pwd-rules" id="fRules">
                <li data-rule="len"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleLen') ?></span></li>
                <li data-rule="upper"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleUpper') ?></span></li>
                <li data-rule="lower"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleLower') ?></span></li>
                <li data-rule="number"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleNumber') ?></span></li>
                <li data-rule="symbol"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleSymbol') ?></span></li>
            </ul>

            <button type="submit" id="fSubmit" class="btn btn-np w-100 py-2 mt-2" disabled>
                <span class="np-btn-label"><i class="bi bi-check-lg me-2"></i><?= lang('Force.submit') ?></span>
                <span class="spinner-border spinner-border-sm text-white d-none" role="status" aria-hidden="true"></span>
            </button>
        </form>

        <div class="text-center mt-3">
            <button type="button" class="np-linkbtn" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-lg me-1"></i><?= lang('Common.cancel') ?></button>
        </div>
    </div>

    <div class="np-login-foot">© <?= date('Y') ?> NetPass · v1.0</div>

    <!-- Modal: เตือนก่อนออกจากระบบ (ยังไม่ตั้งรหัส → เข้าระบบไม่ได้) — dialog โทน warning -->
    <div class="modal fade np-dialog-modal" id="cancelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content np-dialog">
                <div class="dlg-head">
                    <span class="dlg-ico is-warning"><i class="bi bi-exclamation-triangle-fill"></i></span>
                    <div class="dlg-htext">
                        <h5><?= lang('Force.cancelTitle') ?></h5>
                    </div>
                    <button type="button" class="dlg-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="dlg-body is-centered">
                    <div class="dlg-warn-ico is-warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <p><?= lang('Force.cancelWarn') ?></p>
                </div>
                <div class="dlg-foot">
                    <button type="button" class="dlg-btn dlg-btn-light" data-bs-dismiss="modal"><?= lang('Force.cancelStay') ?></button>
                    <a href="<?= site_url('logout') ?>" class="dlg-btn dlg-btn-warning"><i class="bi bi-box-arrow-right"></i> <?= lang('Common.logout') ?></a>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/plugins/bootstrap/bootstrap.bundle.min.js') ?>"></script>
    <?php
    // ค่าจาก server → data island (อ่านโดย JS ภายนอก; CSP script-src ไม่บล็อก JSON ที่ไม่ถูก execute)
    $npFpw = [
        'strength' => ['', lang('Profile.pwdWeak'), lang('Profile.pwdFair'), lang('Profile.pwdGood'), lang('Profile.pwdStrong')],
        'matchOk'  => lang('Profile.confirmMatchOk'),
        'matchBad' => lang('Force.errConfirmMatch'),
    ];
    $fpwJs = FCPATH . 'assets/js/auth/force_password.js';
    ?>
    <script type="application/json" id="np-fpw-data"><?= json_encode($npFpw, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
    <script src="<?= base_url('assets/js/auth/force_password.js') ?>?v=<?= is_file($fpwJs) ? filemtime($fpwJs) : '1' ?>"></script>

</body>
</html>
