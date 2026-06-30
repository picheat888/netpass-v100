<?php
/**
 * เนื้อหาหน้าโปรไฟล์ — ใช้ร่วมกันระหว่าง admin/profile และ user/profile
 * ตัวแปรที่ต้องการ: $profile (array), $user (Shield User entity), $formBase (URL base ของ form)
 *
 * ดีไซน์: การ์ดเดียว — แถบหัว (avatar + ปุ่มเปลี่ยนรูป + ชื่อ/สิทธิ์/เมตา)
 *          ตามด้วย 2 ส่วน (ข้อมูลส่วนตัว / ความปลอดภัย) คั่นด้วยเส้นบาง
 */

// ── เตรียมข้อมูลแถบหัว ──
$pFirst   = (string) ($user->firstname ?? '');
$pLast    = (string) ($user->lastname ?? '');
$fullName = trim($pFirst . ' ' . $pLast);
$display  = $fullName !== '' ? $fullName : $user->username;
$initial  = mb_strtoupper(mb_substr(trim($pFirst . $pLast) !== '' ? $pFirst . $pLast : $user->username, 0, 1));
$isAdmin  = $user->inGroup('admin');
$position = trim((string) ($user->position ?? ''));
$avatarUrl = ! empty($user->img) ? base_url($user->img) : null;
$created  = ! empty($user->created_at) ? date('d/m/Y', strtotime((string) $user->created_at)) : '—';

// ── validation error แบบ inline (มาตรฐาน IT Service: "(i) ข้อความ" สีแดงใต้ช่อง) ──
$infoErrors = (array) (session('prof_errors') ?? []);
$pwdErrors  = (array) (session('pwd_errors') ?? []);
// คืน class "np-invalid" ให้ช่องที่ผิด
$invalid = static fn (array $errs, string $key): string => isset($errs[$key]) ? ' np-invalid' : '';
// คืนบรรทัด error ใต้ช่อง
$fieldErr = static function (array $errs, string $key): string {
    if (empty($errs[$key])) {
        return '';
    }
    return '<p class="np-field-err"><i class="bi bi-info-circle-fill"></i> ' . esc($errs[$key]) . '</p>';
};
?>

<?php /* ข้อความสำเร็จของฟอร์มรหัสผ่าน (pwd_message) แสดงเป็น Toast — ดู templates/toast */ ?>
<div class="np-prof">
    <div class="np-card np-prof-card">

        <!-- ───── แถบหัว: avatar + ชื่อ + สิทธิ์ ───── -->
        <div class="np-prof-head">
            <div class="np-prof-avatar-wrap">
                <!-- คลิกที่รูป/กล้อง → เปิด dialog แสดงรูปโปรไฟล์ -->
                <div class="np-prof-avatar" id="avatarBox" role="button" tabindex="0"
                     data-bs-toggle="modal" data-bs-target="#avatarViewModal"
                     title="<?= esc(lang('Profile.photoTitle'), 'attr') ?>">
                    <?php if ($avatarUrl): ?>
                        <img src="<?= esc($avatarUrl) ?>" alt="">
                    <?php else: ?>
                        <span><?= esc($initial) ?></span>
                    <?php endif ?>
                </div>
                <button type="button" class="np-prof-cam" data-bs-toggle="modal" data-bs-target="#avatarViewModal"
                        title="<?= esc(lang('Profile.changePhoto'), 'attr') ?>">
                    <i class="bi bi-camera-fill"></i>
                </button>
            </div>

            <div class="np-prof-headtext">
                <div class="np-prof-name"><?= esc($display) ?></div>
                <div class="np-prof-headmeta">
                    <span><i class="bi bi-calendar3 me-1"></i><?= lang('Profile.memberSince') ?> <?= esc($created) ?></span>
                </div>
                <?= $fieldErr($infoErrors, 'avatar') ?>
            </div>

            <span class="np-badge <?= $isAdmin ? 'np-badge-blue' : 'np-badge-muted' ?> np-prof-role">
                <i class="bi <?= $isAdmin ? 'bi-shield-lock' : 'bi-person' ?>"></i>
                <?= $isAdmin ? lang('Profile.roleAdmin') : lang('Profile.roleUser') ?>
            </span>
        </div>

        <!-- ───── 2 คอลัมน์: ข้อมูลส่วนตัว | ความปลอดภัย ───── -->
        <div class="np-prof-cols">
        <!-- ───── ส่วน 1: ข้อมูลส่วนตัว ───── -->
        <section class="np-prof-sec">
            <div class="np-prof-sechead">
                <span class="np-prof-secicon"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <h6><?= lang('Profile.infoTitle') ?></h6>
                    <p><?= lang('Profile.infoSub') ?></p>
                </div>
            </div>

            <form method="post" action="<?= site_url($formBase) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <!-- file input ของ avatar (ซ่อน เปิดผ่านปุ่มกล้องบน avatar) -->
                <input type="file" id="avatarInput" name="avatar" accept="image/png,image/jpeg" hidden>

<?php /* ฝั่ง user ล็อกช่อง email/ชื่อ/นามสกุล (admin เป็นผู้กำหนด) — admin ยังแก้ได้ */ ?>
                <div class="np-field">
                    <label class="form-label"><?= lang('Profile.email') ?> <?php if ($isAdmin): ?><span class="np-req">*</span><?php endif ?></label>
                    <input type="email" name="email" class="form-control<?= $invalid($infoErrors, 'email') ?>" maxlength="254"
                           placeholder="<?= esc(lang('Profile.phEmail'), 'attr') ?>"
                           value="<?= esc(old('email', $user->email ?? '')) ?>"<?= $isAdmin ? '' : ' disabled' ?>>
                    <?php if (! $isAdmin): ?><p class="np-field-hint"><i class="bi bi-lock"></i> <?= lang('Profile.fieldLocked') ?></p><?php endif ?>
                    <?= $fieldErr($infoErrors, 'email') ?>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6 np-field">
                        <label class="form-label"><?= lang('Profile.firstName') ?> <?php if ($isAdmin): ?><span class="np-req">*</span><?php endif ?></label>
                        <input type="text" name="firstname" class="form-control<?= $invalid($infoErrors, 'firstname') ?>" maxlength="150"
                               placeholder="<?= esc(lang('Profile.phFirstname'), 'attr') ?>"
                               value="<?= esc(old('firstname', $pFirst)) ?>"<?= $isAdmin ? '' : ' disabled' ?>>
                        <?php if (! $isAdmin): ?><p class="np-field-hint"><i class="bi bi-lock"></i> <?= lang('Profile.fieldLocked') ?></p><?php endif ?>
                        <?= $fieldErr($infoErrors, 'firstname') ?>
                    </div>
                    <div class="col-sm-6 np-field">
                        <label class="form-label"><?= lang('Profile.lastName') ?> <?php if ($isAdmin): ?><span class="np-req">*</span><?php endif ?></label>
                        <input type="text" name="lastname" class="form-control<?= $invalid($infoErrors, 'lastname') ?>" maxlength="150"
                               placeholder="<?= esc(lang('Profile.phLastname'), 'attr') ?>"
                               value="<?= esc(old('lastname', $pLast)) ?>"<?= $isAdmin ? '' : ' disabled' ?>>
                        <?php if (! $isAdmin): ?><p class="np-field-hint"><i class="bi bi-lock"></i> <?= lang('Profile.fieldLocked') ?></p><?php endif ?>
                        <?= $fieldErr($infoErrors, 'lastname') ?>
                    </div>
                </div>
                <div class="np-field">
                    <label class="form-label"><?= lang('Profile.position') ?></label>
                    <input type="text" class="form-control" maxlength="100"
                           value="<?= esc($position) ?>" disabled>
                    <p class="np-field-hint"><i class="bi bi-lock"></i> <?= lang('Profile.positionLocked') ?></p>
                </div>
                <button type="submit" id="profSaveBtn" class="btn btn-np"><?= lang('Common.save') ?></button>
            </form>
        </section>

        <!-- ───── ส่วน 2: ความปลอดภัย ───── -->
        <section class="np-prof-sec">
            <div class="np-prof-sechead">
                <span class="np-prof-secicon"><i class="bi bi-shield-lock"></i></span>
                <div>
                    <h6><?= lang('Profile.pwdTitle') ?></h6>
                    <p><?= lang('Profile.pwdSub') ?></p>
                </div>
            </div>

            <form method="post" action="<?= site_url($formBase . '/password') ?>">
                <?= csrf_field() ?>
                <div class="np-field">
                    <label class="form-label"><?= lang('Profile.username') ?></label>
                    <input type="text" class="form-control" value="<?= esc($user->username) ?>" disabled autocomplete="username">
                </div>
                <div class="np-field">
                    <label class="form-label"><?= lang('Profile.currentPassword') ?> <span class="np-req">*</span></label>
                    <div class="np-pwd-wrap">
                        <input type="password" name="current_password" class="form-control<?= $invalid($pwdErrors, 'current_password') ?>" placeholder="<?= esc(lang('Profile.phCurrentPwd'), 'attr') ?>" autocomplete="current-password">
                        <button type="button" class="np-pwd-toggle" tabindex="-1" aria-label="<?= esc(lang('Profile.togglePwd'), 'attr') ?>"><i class="bi bi-eye"></i></button>
                    </div>
                    <?= $fieldErr($pwdErrors, 'current_password') ?>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6 np-field">
                        <label class="form-label"><?= lang('Profile.newPassword') ?> <span class="np-req">*</span></label>
                        <div class="np-pwd-wrap">
                            <input type="password" name="new_password" class="form-control<?= $invalid($pwdErrors, 'new_password') ?>" placeholder="<?= esc(lang('Profile.phNewPwd'), 'attr') ?>" value="<?= esc(old('new_password')) ?>" autocomplete="new-password">
                            <button type="button" class="np-pwd-toggle" tabindex="-1" aria-label="<?= esc(lang('Profile.togglePwd'), 'attr') ?>"><i class="bi bi-eye"></i></button>
                        </div>
                        <?= $fieldErr($pwdErrors, 'new_password') ?>
                        <!-- แถบวัดความแข็งแรง (อยู่ใต้รหัสใหม่ ฝั่งซ้าย) -->
                        <div class="np-pwd-meter" id="pwdMeter">
                            <div class="np-pwd-meter-track"><span class="np-pwd-meter-fill"></span></div>
                            <span class="np-pwd-meter-label" id="pwdMeterLabel"></span>
                        </div>
                    </div>
                    <div class="col-sm-6 np-field">
                        <label class="form-label"><?= lang('Profile.confirmPassword') ?> <span class="np-req">*</span></label>
                        <div class="np-pwd-wrap">
                            <input type="password" name="confirm_password" class="form-control<?= $invalid($pwdErrors, 'confirm_password') ?>" placeholder="<?= esc(lang('Profile.phConfirmPwd'), 'attr') ?>" value="<?= esc(old('confirm_password')) ?>" autocomplete="new-password">
                            <button type="button" class="np-pwd-toggle" tabindex="-1" aria-label="<?= esc(lang('Profile.togglePwd'), 'attr') ?>"><i class="bi bi-eye"></i></button>
                        </div>
                        <?= $fieldErr($pwdErrors, 'confirm_password') ?>
                        <!-- แจ้งเตือนเรียลไทม์ว่ารหัสตรง/ไม่ตรง -->
                        <p class="np-match-msg" id="confirmMsg"></p>
                    </div>
                </div>
                <!-- เช็คลิสต์เงื่อนไขรหัสผ่าน — ติ๊กถูกเรียลไทม์ตอนพิมพ์ -->
                <ul class="np-pwd-rules" id="pwdRules">
                    <li data-rule="len"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleLen') ?></span></li>
                    <li data-rule="upper"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleUpper') ?></span></li>
                    <li data-rule="lower"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleLower') ?></span></li>
                    <li data-rule="number"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleNumber') ?></span></li>
                    <li data-rule="symbol"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleSymbol') ?></span></li>
                </ul>
                <button type="submit" id="pwdSubmitBtn" class="btn btn-np"><?= lang('Profile.pwdTitle') ?></button>
            </form>
        </section>
        </div>
    </div>
</div>

<!-- Dialog: แสดงรูปโปรไฟล์ + ปุ่มอัปโหลดรูปใหม่ -->
<div class="modal fade" id="avatarViewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-circle me-2"></i><?= lang('Profile.photoTitle') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="np-avatar-view" id="avatarViewBox">
                    <?php if ($avatarUrl): ?>
                        <img src="<?= esc($avatarUrl) ?>" alt="">
                    <?php else: ?>
                        <span><?= esc($initial) ?></span>
                    <?php endif ?>
                </div>
                <!-- ปุ่มนี้เปิด file input (ที่ซ่อนในฟอร์ม) → เข้าสู่ขั้นตอน crop -->
                <label for="avatarInput" class="btn btn-np mt-3">
                    <i class="bi bi-upload me-1"></i><?= lang('Profile.uploadNew') ?>
                </label>
                <p class="np-field-hint justify-content-center mt-2 mb-0" id="avatarHint"><i class="bi bi-image"></i> <?= lang('Profile.avatarHint') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Cropper.js (ครอป/ซูมรูปโปรไฟล์ก่อนอัปโหลด) -->
<link rel="stylesheet" href="<?= base_url('assets/plugins/cropperjs/cropper.min.css') ?>">

<!-- Modal: ปรับรูปโปรไฟล์ -->
<div class="modal fade" id="avatarCropModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-crop me-2"></i><?= lang('Profile.cropTitle') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="avatarCropStage"><img id="avatarCropImg" alt=""></div>
                <div class="d-flex align-items-center gap-2 mt-3">
                    <i class="bi bi-zoom-out text-muted"></i>
                    <input type="range" id="avatarZoom" class="form-range flex-grow-1" min="-0.5" max="0.5" step="0.02" value="0">
                    <i class="bi bi-zoom-in text-muted"></i>
                </div>
                <p class="np-field-hint mt-2 mb-0"><i class="bi bi-arrows-move"></i> <?= lang('Profile.cropHint') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="button" class="btn btn-np" id="avatarCropApply"><i class="bi bi-check-lg me-1"></i><?= lang('Profile.cropApply') ?></button>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/plugins/cropperjs/cropper.min.js') ?>"></script>
<?php
// ข้อความภาษา → data island (อ่านโดย profile.js; CSP script-src ไม่บล็อก JSON ที่ไม่ถูก execute)
$npProfile = [
    'lang' => [
        'avatarBadType' => lang('Profile.avatarBadType'),
        'pwdWeak'   => lang('Profile.pwdWeak'),
        'pwdFair'   => lang('Profile.pwdFair'),
        'pwdGood'   => lang('Profile.pwdGood'),
        'pwdStrong' => lang('Profile.pwdStrong'),
        'matchOk'   => lang('Profile.confirmMatchOk'),
        'matchBad'  => lang('Profile.errConfirmMatch'),
    ],
];
?>
<script type="application/json" id="np-profile-data"><?= json_encode($npProfile, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= base_url('assets/js/profile.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/profile.js') ?>"></script>
