<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
// ── inline validation: เปิด modal เดิมกลับมาพร้อม error ใต้ช่อง (ปกติ client ดักก่อนแล้ว นี่คือ fallback) ──
$mbErrors  = (array) (session('mb_errors') ?? []);
$mbForm    = session('mb_form');        // 'add' | 'edit' | null
$mbEditId  = session('mb_edit_id');
$mbResetId = session('mb_reset_id');
$isAddErr   = $mbForm === 'add';
$isEditErr  = $mbForm === 'edit';
$isResetErr = $mbForm === 'reset';
$invCls  = static fn (bool $enabled, string $field) => ($enabled && isset($mbErrors[$field])) ? ' np-invalid' : '';
$errLine = static fn (bool $enabled, string $field) => ($enabled && ! empty($mbErrors[$field]))
    ? '<p class="np-field-err"><i class="bi bi-info-circle-fill"></i> ' . esc($mbErrors[$field]) . '</p>' : '';
$oldVal  = static fn (bool $enabled, string $field, string $default = '') => $enabled ? esc(old($field, $default)) : esc($default);
?>
<div class="np-card np-dt np-dt-cards">
    <!-- ตัวกรองกลุ่ม (DataTables วางไว้ใน toolbar ซ้าย ถัดจากค้นหา) -->
    <div id="mbToolbar" class="d-flex flex-wrap align-items-center gap-2">
        <select id="mbGroup" class="form-select" style="width:auto">
            <option value=""><?= lang('Member.filterAll') ?></option>
            <option value="admin"><?= lang('Member.roleAdmin') ?></option>
            <option value="user"><?= lang('Member.roleUser') ?></option>
        </select>
    </div>
    <!-- ปุ่มเพิ่มสมาชิก (DataTables วางไว้ใน toolbar ขวา) -->
    <div id="mbAction" class="d-flex gap-2">
        <button class="btn btn-np-outline" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-box-arrow-in-down"></i><?= lang('Member.importBtn') ?>
        </button>
        <button class="btn btn-np" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-person-plus"></i><?= lang('Common.create') ?>
        </button>
    </div>

    <table id="memberTable" class="np-table align-middle" style="width:100%">
        <thead>
            <tr>
                <th><?= lang('Member.colName') ?></th>
                <th><?= lang('Member.colPosition') ?></th>
                <th><?= lang('Member.email') ?></th>
                <th><?= lang('Member.colUsername') ?></th>
                <th><?= lang('Member.colRole') ?></th>
                <th><?= lang('Member.colStatus') ?></th>
                <th class="text-end"><?= lang('Member.colActions') ?></th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>

<?= $this->section('modals') ?>

<!-- Modal: เพิ่มสมาชิก -->
<div class="modal fade np-modal np-modal-member" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form class="modal-content" method="post" action="<?= site_url('admin/members') ?>" enctype="multipart/form-data" id="addForm" novalidate>
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico"><i class="bi bi-person-plus"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Member.addTitle') ?></h5>
                    <p><?= lang('Member.addSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- ───── ส่วนที่ 1: ข้อมูลส่วนตัว ───── -->
                <div class="np-mb-sec">
                    <div class="np-mb-sechead">
                        <span class="np-mb-secicon"><i class="bi bi-person-vcard"></i></span>
                        <div>
                            <h6><?= lang('Member.secPersonal') ?></h6>
                            <p><?= lang('Member.secPersonalSub') ?></p>
                        </div>
                    </div>

                    <!-- รูปโปรไฟล์ (ตัวเลือก) -->
                    <div class="np-mb-avatarrow">
                        <div class="np-mb-avatar" id="addAvatarBox"><span><i class="bi bi-person"></i></span></div>
                        <div>
                            <label for="addAvatarInput" class="btn btn-np-outline btn-sm"><i class="bi bi-upload"></i><?= lang('Profile.uploadNew') ?></label>
                            <p class="np-field-hint mb-0 mt-2" id="addAvatarHint"><i class="bi bi-image"></i> <?= lang('Profile.avatarHint') ?></p>
                            <input type="file" id="addAvatarInput" name="avatar" accept="image/png,image/jpeg" hidden>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6 np-field mb-0">
                            <label class="form-label"><?= lang('Member.firstName') ?> <span class="np-req">*</span></label>
                            <input type="text" name="firstname" class="form-control<?= $invCls($isAddErr, 'firstname') ?>" maxlength="150"
                                   placeholder="<?= esc(lang('Member.phFirstname'), 'attr') ?>"
                                   data-req data-reqmsg="<?= esc(lang('Member.errFirstReq'), 'attr') ?>"
                                   value="<?= $oldVal($isAddErr, 'firstname') ?>">
                            <?= $errLine($isAddErr, 'firstname') ?>
                        </div>
                        <div class="col-sm-6 np-field mb-0">
                            <label class="form-label"><?= lang('Member.lastName') ?> <span class="np-req">*</span></label>
                            <input type="text" name="lastname" class="form-control<?= $invCls($isAddErr, 'lastname') ?>" maxlength="150"
                                   placeholder="<?= esc(lang('Member.phLastname'), 'attr') ?>"
                                   data-req data-reqmsg="<?= esc(lang('Member.errLastReq'), 'attr') ?>"
                                   value="<?= $oldVal($isAddErr, 'lastname') ?>">
                            <?= $errLine($isAddErr, 'lastname') ?>
                        </div>
                        <div class="col-sm-6 np-field mb-0">
                            <label class="form-label"><?= lang('Member.email') ?></label>
                            <input type="email" name="email" class="form-control<?= $invCls($isAddErr, 'email') ?>" maxlength="254"
                                   placeholder="<?= esc(lang('Member.phEmail'), 'attr') ?>"
                                   data-fmtmsg="<?= esc(lang('Member.errEmailValid'), 'attr') ?>"
                                   value="<?= $oldVal($isAddErr, 'email') ?>">
                            <?= $errLine($isAddErr, 'email') ?>
                        </div>
                        <div class="col-sm-6 np-field mb-0">
                            <label class="form-label"><?= lang('Member.position') ?> <span class="np-req">*</span></label>
                            <input type="text" name="position" class="form-control<?= $invCls($isAddErr, 'position') ?>" maxlength="100"
                                   placeholder="<?= esc(lang('Member.phPosition'), 'attr') ?>"
                                   data-req data-reqmsg="<?= esc(lang('Member.errPositionReq'), 'attr') ?>"
                                   value="<?= $oldVal($isAddErr, 'position') ?>">
                            <?= $errLine($isAddErr, 'position') ?>
                        </div>
                    </div>
                </div>

                <!-- ───── ส่วนที่ 2: บัญชีและสิทธิ์ ───── -->
                <div class="np-mb-sec">
                    <div class="np-mb-sechead">
                        <span class="np-mb-secicon"><i class="bi bi-shield-lock"></i></span>
                        <div>
                            <h6><?= lang('Member.secAccount') ?></h6>
                            <p><?= lang('Member.secAccountSub') ?></p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6 np-field mb-0">
                            <label class="form-label"><?= lang('Member.role') ?></label>
                            <select name="role" class="form-select">
                                <option value="user"  <?= old('role') === 'user'  ? 'selected' : '' ?>><?= lang('Member.roleUser') ?></option>
                                <option value="admin" <?= old('role') === 'admin' ? 'selected' : '' ?>><?= lang('Member.roleAdmin') ?></option>
                            </select>
                        </div>
                        <div class="col-sm-6 np-field mb-0">
                            <label class="form-label"><?= lang('Member.username') ?> <span class="np-req">*</span></label>
                            <input type="text" name="username" class="form-control font-mono<?= $invCls($isAddErr, 'username') ?>" maxlength="100"
                                   placeholder="<?= esc(lang('Member.phUsername'), 'attr') ?>"
                                   data-req data-reqmsg="<?= esc(lang('Member.errUsernameReq'), 'attr') ?>"
                                   value="<?= $oldVal($isAddErr, 'username') ?>">
                            <?= $errLine($isAddErr, 'username') ?>
                        </div>
                        <div class="col-12 np-field mb-0">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0"><?= lang('Member.password') ?> <span class="np-req">*</span></label>
                                <button type="button" class="np-linkbtn" id="addRandomPwd"><i class="bi bi-shuffle"></i> <?= lang('Member.randomPwd') ?></button>
                            </div>
                            <div class="np-pwd-wrap">
                                <input type="password" name="password" id="addPwd" class="form-control<?= $invCls($isAddErr, 'password') ?>" autocomplete="new-password"
                                       placeholder="<?= esc(lang('Member.phPassword'), 'attr') ?>"
                                       data-req data-reqmsg="<?= esc(lang('Member.errPwdReq'), 'attr') ?>"
                                       data-weakmsg="<?= esc(lang('Member.errPwdWeak'), 'attr') ?>">
                                <button type="button" class="np-pwd-toggle" tabindex="-1" aria-label="<?= esc(lang('Profile.togglePwd'), 'attr') ?>"><i class="bi bi-eye"></i></button>
                            </div>
                            <?= $errLine($isAddErr, 'password') ?>
                            <div class="np-pwd-meter" id="addPwdMeter">
                                <div class="np-pwd-meter-track"><span class="np-pwd-meter-fill"></span></div>
                                <span class="np-pwd-meter-label" id="addPwdMeterLabel"></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <ul class="np-pwd-rules" id="addPwdRules">
                                <li data-rule="len"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleLen') ?></span></li>
                                <li data-rule="upper"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleUpper') ?></span></li>
                                <li data-rule="lower"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleLower') ?></span></li>
                                <li data-rule="number"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleNumber') ?></span></li>
                                <li data-rule="symbol"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleSymbol') ?></span></li>
                            </ul>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="force_change" value="1" id="addForceChange" checked>
                                <label class="form-check-label" for="addForceChange"><?= lang('Member.forceChange') ?></label>
                            </div>
                            <p class="np-field-hint mb-0 mt-1"><i class="bi bi-info-circle"></i> <?= lang('Member.forceChangeHint') ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="btn btn-np"><i class="bi bi-check-lg"></i><?= lang('Common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: แก้ไขสมาชิก -->
<div class="modal fade np-modal np-modal-member" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form class="modal-content" method="post" id="editForm" enctype="multipart/form-data" novalidate
              action="<?= $isEditErr ? site_url('admin/members/' . $mbEditId . '/update') : '' ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico"><i class="bi bi-pencil"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Member.editTitle') ?></h5>
                    <p><?= lang('Member.editSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="np-mb-avatarrow">
                    <div class="np-mb-avatar" id="editAvatarBox"><span></span></div>
                    <div>
                        <label for="editAvatarInput" class="btn btn-np-outline btn-sm"><i class="bi bi-upload"></i><?= lang('Profile.uploadNew') ?></label>
                        <p class="np-field-hint mb-0 mt-2" id="editAvatarHint"><i class="bi bi-image"></i> <?= lang('Profile.avatarHint') ?></p>
                        <input type="file" id="editAvatarInput" name="avatar" accept="image/png,image/jpeg" hidden>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6 np-field mb-0">
                        <label class="form-label"><?= lang('Member.firstName') ?> <span class="np-req">*</span></label>
                        <input type="text" name="firstname" id="editFirstname" class="form-control<?= $invCls($isEditErr, 'firstname') ?>" maxlength="150"
                               placeholder="<?= esc(lang('Member.phFirstname'), 'attr') ?>"
                               data-req data-reqmsg="<?= esc(lang('Member.errFirstReq'), 'attr') ?>"
                               value="<?= $oldVal($isEditErr, 'firstname') ?>">
                        <?= $errLine($isEditErr, 'firstname') ?>
                    </div>
                    <div class="col-sm-6 np-field mb-0">
                        <label class="form-label"><?= lang('Member.lastName') ?> <span class="np-req">*</span></label>
                        <input type="text" name="lastname" id="editLastname" class="form-control<?= $invCls($isEditErr, 'lastname') ?>" maxlength="150"
                               placeholder="<?= esc(lang('Member.phLastname'), 'attr') ?>"
                               data-req data-reqmsg="<?= esc(lang('Member.errLastReq'), 'attr') ?>"
                               value="<?= $oldVal($isEditErr, 'lastname') ?>">
                        <?= $errLine($isEditErr, 'lastname') ?>
                    </div>
                    <div class="col-sm-6 np-field mb-0">
                        <label class="form-label"><?= lang('Member.email') ?></label>
                        <input type="email" name="email" id="editEmail" class="form-control<?= $invCls($isEditErr, 'email') ?>" maxlength="254"
                               placeholder="<?= esc(lang('Member.phEmail'), 'attr') ?>"
                               data-fmtmsg="<?= esc(lang('Member.errEmailValid'), 'attr') ?>"
                               value="<?= $oldVal($isEditErr, 'email') ?>">
                        <?= $errLine($isEditErr, 'email') ?>
                    </div>
                    <div class="col-sm-6 np-field mb-0">
                        <label class="form-label"><?= lang('Member.position') ?> <span class="np-req">*</span></label>
                        <input type="text" name="position" id="editPosition" class="form-control<?= $invCls($isEditErr, 'position') ?>" maxlength="100"
                               placeholder="<?= esc(lang('Member.phPosition'), 'attr') ?>"
                               data-req data-reqmsg="<?= esc(lang('Member.errPositionReq'), 'attr') ?>"
                               value="<?= $oldVal($isEditErr, 'position') ?>">
                        <?= $errLine($isEditErr, 'position') ?>
                    </div>
                    <div class="col-sm-6 np-field mb-0">
                        <label class="form-label"><?= lang('Member.username') ?></label>
                        <input type="text" id="editUsername" class="form-control font-mono" disabled>
                        <p class="np-field-hint mb-0"><i class="bi bi-lock"></i> <?= lang('Member.usernameLocked') ?></p>
                    </div>
                    <div class="col-sm-6 np-field mb-0">
                        <label class="form-label"><?= lang('Member.role') ?></label>
                        <select name="role" id="editRole" class="form-select">
                            <option value="user"><?= lang('Member.roleUser') ?></option>
                            <option value="admin"><?= lang('Member.roleAdmin') ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="btn btn-np" id="editSaveBtn"><i class="bi bi-check-lg"></i><?= lang('Common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Dialog: review ก่อนบันทึกการแก้ไขสมาชิก (confirm + ตารางเทียบค่าเดิม → ใหม่) -->
<div class="modal fade np-dialog-modal" id="mbEditReviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content np-dialog">
            <div class="dlg-head">
                <span class="dlg-ico is-confirm"><i class="bi bi-pencil-square"></i></span>
                <div class="dlg-htext">
                    <h5><?= lang('Member.editReviewTitle') ?></h5>
                    <p><?= lang('Member.editReviewSub') ?></p>
                </div>
                <button type="button" class="dlg-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="dlg-body">
                <table class="dlg-cmp-table">
                    <thead>
                        <tr><th><?= lang('Common.changeField') ?></th><th><?= lang('Common.changeOld') ?></th><th><?= lang('Common.changeNew') ?></th></tr>
                    </thead>
                    <tbody id="editReviewRows"></tbody>
                </table>
            </div>
            <div class="dlg-foot">
                <button type="button" class="dlg-btn dlg-btn-light" data-bs-dismiss="modal"><i class="bi bi-chevron-left"></i> <?= lang('Member.summaryEdit') ?></button>
                <button type="button" class="dlg-btn dlg-btn-confirm" id="mbEditReviewConfirm"><i class="bi bi-check-lg"></i> <?= lang('Member.summaryConfirm') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Dialog: สรุปข้อมูลก่อนยืนยันสร้างสมาชิก (confirm + ตารางค่า + บัญชีเข้าใช้งาน) -->
<div class="modal fade np-dialog-modal" id="mbSummaryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content np-dialog">
            <div class="dlg-head">
                <span class="dlg-ico is-confirm"><i class="bi bi-clipboard-check"></i></span>
                <div class="dlg-htext">
                    <h5><?= lang('Member.summaryTitle') ?></h5>
                    <p><?= lang('Member.summarySub') ?></p>
                </div>
                <button type="button" class="dlg-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="dlg-body">
                <table class="dlg-cmp-table">
                    <thead>
                        <tr><th><?= lang('Common.changeField') ?></th><th><?= lang('Common.changeValue') ?></th></tr>
                    </thead>
                    <tbody id="sumRows"></tbody>
                </table>
                <!-- บัญชีเข้าใช้งาน: ให้ admin คัดลอกไปส่งผู้ใช้ -->
                <div class="np-cred-box">
                    <div class="np-cred-line">
                        <div><div class="np-cred-k"><?= lang('Member.username') ?></div><div class="np-cred-v font-mono" id="sumUser"></div></div>
                        <button type="button" class="np-icon-sm" data-copy="sumUser" title="<?= esc(lang('Voucher.copyUser'), 'attr') ?>"><i class="bi bi-copy"></i></button>
                    </div>
                    <div class="np-cred-line">
                        <div><div class="np-cred-k"><?= lang('Member.password') ?></div><div class="np-cred-v font-mono" id="sumPass"></div></div>
                        <button type="button" class="np-icon-sm" data-copy="sumPass" title="<?= esc(lang('Voucher.copyPass'), 'attr') ?>"><i class="bi bi-copy"></i></button>
                    </div>
                </div>
                <p class="np-field-hint mt-3 mb-0" id="sumForceNote"></p>
            </div>
            <div class="dlg-foot">
                <button type="button" class="dlg-btn dlg-btn-light" id="mbSummaryBack" data-bs-dismiss="modal"><i class="bi bi-chevron-left"></i> <?= lang('Member.summaryEdit') ?></button>
                <button type="button" class="dlg-btn dlg-btn-confirm" id="mbSummaryConfirm"><i class="bi bi-check-lg"></i> <?= lang('Member.summaryConfirm') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: ยืนยันปิดใช้งานบัญชี (warning) -->
<div class="modal fade np-dialog-modal" id="mbDeactivateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content np-dialog" method="post" id="mbDeactivateForm" action="">
            <?= csrf_field() ?>
            <div class="dlg-head">
                <span class="dlg-ico is-warning"><i class="bi bi-power"></i></span>
                <div class="dlg-htext">
                    <h5><?= lang('Member.deactivateTitle') ?></h5>
                    <p><?= lang('Member.deactivateSub') ?></p>
                </div>
                <button type="button" class="dlg-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                <div class="dlg-callout is-warning">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><?= lang('Member.deactivateImpact') ?></span>
                </div>
            </div>
            <div class="dlg-body is-centered">
                <div class="dlg-warn-ico is-warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <p><?= lang('Member.deactivateConfirm') ?></p>
                <p class="dlg-target" id="mbDeactivateName"></p>
            </div>
            <div class="dlg-foot">
                <button type="button" class="dlg-btn dlg-btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="dlg-btn dlg-btn-warning"><i class="bi bi-check-lg"></i> <?= lang('Member.deactivateBtn') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: ยืนยันเปิดใช้งานบัญชี (confirm) -->
<div class="modal fade np-dialog-modal" id="mbActivateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content np-dialog" method="post" id="mbActivateForm" action="">
            <?= csrf_field() ?>
            <div class="dlg-head">
                <span class="dlg-ico is-confirm"><i class="bi bi-power"></i></span>
                <div class="dlg-htext">
                    <h5><?= lang('Member.activateTitle') ?></h5>
                    <p><?= lang('Member.activateSub') ?></p>
                </div>
                <button type="button" class="dlg-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="dlg-body is-centered">
                <div class="dlg-warn-ico is-confirm"><i class="bi bi-check-circle-fill"></i></div>
                <p><?= lang('Member.activateConfirm') ?></p>
                <p class="dlg-target" id="mbActivateName"></p>
            </div>
            <div class="dlg-foot">
                <button type="button" class="dlg-btn dlg-btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="dlg-btn dlg-btn-confirm"><i class="bi bi-check-lg"></i> <?= lang('Member.activateBtn') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: ยืนยันลบสมาชิก (delete) -->
<div class="modal fade np-dialog-modal" id="mbDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content np-dialog" method="post" id="mbDeleteForm" action="">
            <?= csrf_field() ?>
            <div class="dlg-head">
                <span class="dlg-ico is-delete"><i class="bi bi-trash3-fill"></i></span>
                <div class="dlg-htext">
                    <h5><?= lang('Member.deleteTitle') ?></h5>
                    <p><?= lang('Member.deleteSub') ?></p>
                </div>
                <button type="button" class="dlg-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                <div class="dlg-callout is-delete">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <span><?= lang('Member.deleteImpact') ?></span>
                </div>
            </div>
            <div class="dlg-body is-centered">
                <div class="dlg-warn-ico is-delete"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <p><?= lang('Member.confirmDelete') ?></p>
                <p class="dlg-target" id="mbDeleteName"></p>
            </div>
            <div class="dlg-foot">
                <button type="button" class="dlg-btn dlg-btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="dlg-btn dlg-btn-delete"><i class="bi bi-trash"></i> <?= lang('Common.delete') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: รีเซ็ตรหัสผ่าน -->
<div class="modal fade np-modal np-modal-member" id="mbResetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="mbResetForm" novalidate
              action="<?= $isResetErr ? site_url('admin/members/' . $mbResetId . '/reset-password') : '' ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico"><i class="bi bi-key"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Member.resetTitle') ?></h5>
                    <p><?= lang('Member.resetSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="np-field">
                    <label class="form-label"><?= lang('Member.username') ?></label>
                    <input type="text" id="rsUsername" class="form-control font-mono" disabled>
                </div>
                <div class="np-field mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0"><?= lang('Member.newPassword') ?> <span class="np-req">*</span></label>
                        <button type="button" class="np-linkbtn" id="rsRandomPwd"><i class="bi bi-shuffle"></i> <?= lang('Member.randomPwd') ?></button>
                    </div>
                    <div class="np-pwd-wrap">
                        <input type="password" name="new_password" id="rsPwd" class="form-control<?= $invCls($isResetErr, 'new_password') ?>" autocomplete="new-password"
                               data-req data-reqmsg="<?= esc(lang('Member.errPwdReq'), 'attr') ?>"
                               data-weakmsg="<?= esc(lang('Member.errPwdWeak'), 'attr') ?>">
                        <button type="button" class="np-pwd-toggle" tabindex="-1" aria-label="<?= esc(lang('Profile.togglePwd'), 'attr') ?>"><i class="bi bi-eye"></i></button>
                    </div>
                    <?= $errLine($isResetErr, 'new_password') ?>
                    <div class="np-pwd-meter" id="rsPwdMeter">
                        <div class="np-pwd-meter-track"><span class="np-pwd-meter-fill"></span></div>
                        <span class="np-pwd-meter-label" id="rsPwdMeterLabel"></span>
                    </div>
                </div>
                <ul class="np-pwd-rules mt-2" id="rsPwdRules">
                    <li data-rule="len"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleLen') ?></span></li>
                    <li data-rule="upper"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleUpper') ?></span></li>
                    <li data-rule="lower"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleLower') ?></span></li>
                    <li data-rule="number"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleNumber') ?></span></li>
                    <li data-rule="symbol"><i class="bi bi-circle"></i><span><?= lang('Profile.ruleSymbol') ?></span></li>
                </ul>
                <div class="mt-3">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="force_change" value="1" id="rsForceChange" checked>
                        <label class="form-check-label" for="rsForceChange"><?= lang('Member.forceChange') ?></label>
                    </div>
                    <p class="np-field-hint mb-0 mt-1"><i class="bi bi-info-circle"></i> <?= lang('Member.forceChangeHint') ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="btn btn-np"><i class="bi bi-key"></i><?= lang('Member.resetBtn') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: ปรับรูปโปรไฟล์ (ใช้ร่วมทั้งเพิ่ม/แก้ไข) -->
<link rel="stylesheet" href="<?= base_url('assets/plugins/cropperjs/cropper.min.css') ?>">
<div class="modal fade np-modal" id="mbCropModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="np-modal-ico"><i class="bi bi-crop"></i></span>
                <div class="np-modal-htext"><h5><?= lang('Profile.cropTitle') ?></h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="mbCropStage"><img id="mbCropImg" alt=""></div>
                <div class="d-flex align-items-center gap-2 mt-3">
                    <i class="bi bi-zoom-out text-muted"></i>
                    <input type="range" id="mbZoom" class="form-range flex-grow-1" min="-0.5" max="0.5" step="0.02" value="0">
                    <i class="bi bi-zoom-in text-muted"></i>
                </div>
                <p class="np-field-hint mt-2 mb-0"><i class="bi bi-arrows-move"></i> <?= lang('Profile.cropHint') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="button" class="btn btn-np" id="mbCropApply"><i class="bi bi-check-lg"></i><?= lang('Profile.cropApply') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: นำเข้าสมาชิก -->
<div class="modal fade np-modal np-modal-confirm" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="importForm" method="post" action="<?= site_url('admin/members/import') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico is-confirm"><i class="bi bi-box-arrow-in-down"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Member.importTitle') ?></h5>
                    <p><?= lang('Member.importSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-end mb-2">
                    <a href="<?= site_url('admin/members/import/template') ?>" class="np-tpl-chip"><i class="bi bi-download"></i><?= lang('Member.importDownloadTpl') ?></a>
                </div>

                <!-- dropzone: ลากวาง/คลิกเลือก — input ไฟล์จริงซ่อนเต็มพื้นที่ -->
                <div class="np-drop" id="importDrop" tabindex="0" role="button" aria-label="<?= esc(lang('Member.importChooseFile'), 'attr') ?>">
                    <input type="file" name="file" id="importFile" accept=".csv">
                    <div class="np-drop-ico"><i class="bi bi-filetype-csv"></i></div>
                    <div class="np-drop-title"><?= lang('Member.importDropTitle') ?> <span class="np-drop-browse"><?= lang('Member.importDropBrowse') ?></span></div>
                    <div class="np-drop-meta"><?= lang('Member.importDropMeta') ?></div>
                </div>

                <!-- file chip: โชว์แทน dropzone เมื่อเลือกไฟล์แล้ว -->
                <div class="np-drop-file d-none" id="importFileChip">
                    <span class="fico"><i class="bi bi-filetype-csv"></i></span>
                    <div class="meta">
                        <div class="name" id="importFileName">—</div>
                        <div class="size" id="importFileSize"></div>
                    </div>
                    <button type="button" class="np-drop-remove" id="importFileRemove" aria-label="<?= esc(lang('Member.importRemoveFile'), 'attr') ?>"><i class="bi bi-x-lg"></i></button>
                </div>

                <p class="np-field-err d-none mt-2" id="importErr"><i class="bi bi-info-circle-fill"></i> <span></span></p>

                <div class="np-callout is-warning mt-3">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><?= lang('Member.importNote') ?></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" id="importSubmit" class="btn btn-np">
                    <span class="np-btn-label"><i class="bi bi-box-arrow-in-down me-1"></i><?= lang('Member.importSubmit') ?></span>
                    <span class="spinner-border spinner-border-sm text-white d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: ผลการนำเข้า -->
<div class="modal fade np-modal np-modal-confirm" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="np-modal-ico is-confirm"><i class="bi bi-clipboard-check"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Member.importResultTitle') ?></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="np-import-ring" id="resRing" style="--pct:0"><span id="resPercent">0%</span></div>
                <div class="d-flex justify-content-center gap-4 mt-3">
                    <div><span class="fw-bold text-success" id="resSuccess">0</span> <span class="np-stat-sub"><?= lang('Member.importSuccessCount') ?></span></div>
                    <div><span class="fw-bold text-danger" id="resFail">0</span> <span class="np-stat-sub"><?= lang('Member.importFailCount') ?></span></div>
                </div>
                <div class="mt-3 text-start d-none" id="resFailWrap">
                    <table id="resFailTable" class="np-table align-middle" style="width:100%">
                        <thead><tr>
                            <th><?= lang('Member.importColRow') ?></th>
                            <th><?= lang('Member.importColUser') ?></th>
                            <th><?= lang('Member.importColReason') ?></th>
                        </tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-np" data-bs-dismiss="modal"><?= lang('Member.importClose') ?></button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/plugins/cropperjs/cropper.min.js') ?>"></script>
<script>
// ───────── ค่าจาก server (PHP อยู่ที่นี่ที่เดียว) ─────────
window.NP_MEMBERS = {
    urls: {
        data: '<?= site_url('admin/members/data') ?>',
    },
    csrf: {
        name: '<?= csrf_token() ?>',
    },
    i18n: {
        strength:    ['', <?= json_encode(lang('Profile.pwdWeak')) ?>, <?= json_encode(lang('Profile.pwdFair')) ?>, <?= json_encode(lang('Profile.pwdGood')) ?>, <?= json_encode(lang('Profile.pwdStrong')) ?>],
        badType:     <?= json_encode(lang('Profile.avatarBadType')) ?>,
        forceNote:   <?= json_encode(lang('Member.forceChangeNote')) ?>,
        colName:     <?= json_encode(lang('Member.colName')) ?>,
        email:       <?= json_encode(lang('Member.email')) ?>,
        position:    <?= json_encode(lang('Member.position')) ?>,
        role:        <?= json_encode(lang('Member.role')) ?>,
        firstName:   <?= json_encode(lang('Member.firstName')) ?>,
        lastName:    <?= json_encode(lang('Member.lastName')) ?>,
        roleLbl:     <?= json_encode(lang('Member.role')) ?>,
        adminLbl:    <?= json_encode(lang('Member.roleAdmin')) ?>,
        userLbl:     <?= json_encode(lang('Member.roleUser')) ?>,
        photoLbl:    <?= json_encode(lang('Profile.photoTitle')) ?>,
        noChange:    <?= json_encode(lang('Member.editNoChange')) ?>,
        uploadNew:   <?= json_encode(lang('Profile.uploadNew')) ?>,
        noFile:      <?= json_encode(lang('Member.importNoFile')) ?>,
        badFile:     <?= json_encode(lang('Member.importBadFile')) ?>,
    },
    openModal: '<?= $isAddErr ? 'add' : ($isEditErr ? 'edit' : '') ?>',
};
</script>
<script><?= file_get_contents(__DIR__ . '/index.js') ?></script>
<?= $this->endSection() ?>
