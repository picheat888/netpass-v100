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

    <!-- Modal: เตือนก่อนออกจากระบบ (ยังไม่ตั้งรหัส → เข้าระบบไม่ได้) — ใช้ดีไซน์ np-modal-confirm เดียวกับทั้งระบบ -->
    <div class="modal fade np-modal np-modal-confirm" id="cancelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="np-modal-ico is-warning"><i class="bi bi-exclamation-triangle-fill"></i></span>
                    <div class="np-modal-htext">
                        <h5><?= lang('Force.cancelTitle') ?></h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="np-callout is-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?= lang('Force.cancelWarn') ?></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Force.cancelStay') ?></button>
                    <a href="<?= site_url('logout') ?>" class="btn btn-danger"><i class="bi bi-box-arrow-right me-1"></i><?= lang('Common.logout') ?></a>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/plugins/bootstrap/bootstrap.bundle.min.js') ?>"></script>
    <script>
    (function () {
        var newPwd = document.getElementById('fNew');
        var confirmPwd = document.getElementById('fConfirm');
        var rulesBox = document.getElementById('fRules');
        var meter = document.getElementById('fMeter');
        var meterLabel = document.getElementById('fMeterLabel');
        var matchMsg = document.getElementById('fMatch');
        var submitBtn = document.getElementById('fSubmit');
        var rulesPassed = 0;   // จำนวนกฎที่ผ่านล่าสุด (อัปเดตใน evaluate)
        var STRENGTH = ['', <?= json_encode(lang('Profile.pwdWeak')) ?>, <?= json_encode(lang('Profile.pwdFair')) ?>, <?= json_encode(lang('Profile.pwdGood')) ?>, <?= json_encode(lang('Profile.pwdStrong')) ?>];
        var MATCH_OK = <?= json_encode(lang('Profile.confirmMatchOk')) ?>;
        var MATCH_BAD = <?= json_encode(lang('Force.errConfirmMatch')) ?>;
        var tests = {
            len: function (value) { return value.length >= 8; },
            upper: function (value) { return /[A-Z]/.test(value); },
            lower: function (value) { return /[a-z]/.test(value); },
            number: function (value) { return /[0-9]/.test(value); },
            symbol: function (value) { return /[^A-Za-z0-9]/.test(value); }
        };
        function evaluate() {
            var value = newPwd.value, passed = 0;
            rulesBox.querySelectorAll('li').forEach(function (item) {
                var ok = tests[item.dataset.rule](value);
                if (ok) passed++;
                item.classList.toggle('ok', ok);
                item.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            });
            var level = value.length === 0 ? 0 : (passed <= 2 ? 1 : (passed === 3 ? 2 : (passed === 4 ? 3 : 4)));
            meter.dataset.lvl = level;
            meterLabel.textContent = STRENGTH[level];
            rulesPassed = passed;
            refreshSubmit();
        }
        function checkMatch() {
            if (confirmPwd.value === '') { matchMsg.className = 'np-match-msg'; matchMsg.innerHTML = ''; }
            else if (confirmPwd.value === newPwd.value) { matchMsg.className = 'np-match-msg is-ok'; matchMsg.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + MATCH_OK; }
            else { matchMsg.className = 'np-match-msg is-bad'; matchMsg.innerHTML = '<i class="bi bi-info-circle-fill"></i> ' + MATCH_BAD; }
            refreshSubmit();
        }

        // เปิดปุ่ม submit เมื่อกฎผ่านครบ 5 และ confirm ตรงกับ new password
        function refreshSubmit() {
            var ok = rulesPassed === 5 && confirmPwd.value !== '' && confirmPwd.value === newPwd.value;
            submitBtn.disabled = ! ok;
        }
        newPwd.addEventListener('input', function () {
            if (newPwd.value.length >= 1) { rulesBox.classList.add('show'); meter.classList.add('show'); }
            else { rulesBox.classList.remove('show'); meter.classList.remove('show'); }
            evaluate(); checkMatch();
        });
        confirmPwd.addEventListener('input', checkMatch);
        document.querySelectorAll('.np-pwd-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var inp = btn.parentElement.querySelector('input');
                var reveal = inp.type === 'password';
                inp.type = reveal ? 'text' : 'password';
                btn.querySelector('i').className = reveal ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });

        // ตอน submit: spinner กลางปุ่ม + disable กัน double-submit
        document.querySelector('form').addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.querySelector('.np-btn-label').classList.add('d-none');
            submitBtn.querySelector('.spinner-border').classList.remove('d-none');
        });
    })();
    </script>

</body>
</html>
