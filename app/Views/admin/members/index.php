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
                <t1h><?= lang('Member.colPosition') ?></t1h>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
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

<!-- Modal: ยืนยันการเปลี่ยนแปลง (review ก่อนบันทึกการแก้ไข) -->
<div class="modal fade np-modal" id="mbEditReviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="np-modal-ico"><i class="bi bi-pencil-square"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Member.editReviewTitle') ?></h5>
                    <p><?= lang('Member.editReviewSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="editReviewRows"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><i class="bi bi-chevron-left"></i><?= lang('Member.summaryEdit') ?></button>
                <button type="button" class="btn btn-np" id="mbEditReviewConfirm"><i class="bi bi-check-lg"></i><?= lang('Member.summaryConfirm') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: สรุปข้อมูลก่อนยืนยันสร้างสมาชิก -->
<div class="modal fade np-modal" id="mbSummaryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="np-modal-ico"><i class="bi bi-clipboard-check"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Member.summaryTitle') ?></h5>
                    <p><?= lang('Member.summarySub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="sumRows"></div>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="mbSummaryBack" data-bs-dismiss="modal"><i class="bi bi-chevron-left"></i><?= lang('Member.summaryEdit') ?></button>
                <button type="button" class="btn btn-np" id="mbSummaryConfirm"><i class="bi bi-check-lg"></i><?= lang('Member.summaryConfirm') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: ยืนยันปิดใช้งานบัญชี (Warning) -->
<div class="modal fade np-modal np-modal-confirm" id="mbDeactivateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="mbDeactivateForm" action="">
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico is-warning"><i class="bi bi-power"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Member.deactivateTitle') ?></h5>
                    <p><?= lang('Member.deactivateSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <div class="np-callout is-warning">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><?= lang('Member.deactivateImpact') ?></span>
                </div>
            </div>
            <div class="modal-body">
                <div class="text-center mb-2">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:2.75rem;color:var(--np-amber)"></i>
                </div>
                <p class="mb-0 text-center"><?= lang('Member.deactivateConfirm') ?></p>
                <p class="fw-semibold mt-1 mb-0 text-center" id="mbDeactivateName"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="btn btn-np-warning"><i class="bi bi-check-lg"></i><?= lang('Member.deactivateBtn') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: ยืนยันเปิดใช้งานบัญชี (Confirm) -->
<div class="modal fade np-modal np-modal-confirm" id="mbActivateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="mbActivateForm" action="">
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico is-confirm"><i class="bi bi-power"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Member.activateTitle') ?></h5>
                    <p><?= lang('Member.activateSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-2">
                    <i class="bi bi-check-circle-fill" style="font-size:2.75rem;color:var(--np-blue)"></i>
                </div>
                <p class="mb-0 text-center"><?= lang('Member.activateConfirm') ?></p>
                <p class="fw-semibold mt-1 mb-0 text-center" id="mbActivateName"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="btn btn-np"><i class="bi bi-check-lg"></i><?= lang('Member.activateBtn') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: ยืนยันลบสมาชิก -->
<div class="modal fade np-modal np-modal-confirm" id="mbDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="mbDeleteForm" action="">
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico is-danger"><i class="bi bi-trash3-fill"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Member.deleteTitle') ?></h5>
                    <p><?= lang('Member.deleteSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <div class="np-callout is-danger">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <span><?= lang('Member.deleteImpact') ?></span>
                </div>
            </div>
            <div class="modal-body">
                <div class="text-center mb-2">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:2.75rem;color:var(--np-red)"></i>
                </div>
                <p class="mb-0 text-center"><?= lang('Member.confirmDelete') ?></p>
                <p class="fw-semibold mt-1 mb-0 text-center" id="mbDeleteName"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i><?= lang('Common.delete') ?></button>
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
// เติมข้อมูลใน edit modal จาก data attribute ของปุ่ม
document.getElementById('editModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn) return;   // เปิดเองตอน validation error → ใช้ค่าที่ server เติมไว้
    const d = btn.dataset;
    document.getElementById('editForm').action     = '/admin/members/' + d.id + '/update';
    document.getElementById('editEmail').value      = d.email || '';
    document.getElementById('editFirstname').value  = d.firstname || '';
    document.getElementById('editLastname').value   = d.lastname || '';
    document.getElementById('editUsername').value   = d.username || '';
    document.getElementById('editPosition').value   = d.position || '';
    // role ถูกแปลงเป็น Tom Select แล้ว → ตั้งค่าผ่าน API (ตั้ง .value เฉยๆ ไม่อัปเดตหน้าตา)
    const roleEl = document.getElementById('editRole');
    if (roleEl.tomselect) { roleEl.tomselect.setValue(d.role || 'user'); }
    else { roleEl.value = d.role || 'user'; }
    // รูปปัจจุบัน (หรือตัวอักษรย่อ)
    const box = document.getElementById('editAvatarBox');
    box.innerHTML = d.img ? '<img src="' + d.img + '" alt="">' : '<span>' + (d.initial || '') + '</span>';
    document.getElementById('editAvatarInput').value = '';
});

// เติม action + ชื่อใน delete modal
document.getElementById('mbDeleteModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn) return;
    document.getElementById('mbDeleteForm').action = '/admin/members/' + btn.dataset.id + '/delete';
    // ครอบชื่อด้วยเครื่องหมาย "…" ให้เด่น (ว่างเปล่า → ไม่ใส่)
    document.getElementById('mbDeleteName').textContent = btn.dataset.name ? '"' + btn.dataset.name + '"' : '';
});

// เติม action + username ใน reset-password modal (ล้างช่องรหัสเดิม)
document.getElementById('mbResetModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn) return;
    document.getElementById('mbResetForm').action = '/admin/members/' + btn.dataset.id + '/reset-password';
    document.getElementById('rsUsername').value = btn.dataset.username || '';
    const pwd = document.getElementById('rsPwd');
    pwd.value = ''; pwd.type = 'password'; pwd.classList.remove('np-invalid');
    document.getElementById('rsForceChange').checked = true;
    document.getElementById('rsPwdRules').classList.remove('show');
    document.getElementById('rsPwdMeter').classList.remove('show');
    const e2 = document.querySelector('#mbResetForm .np-field-err'); if (e2) e2.remove();
});

// สลับสถานะบัญชี: ปิดใช้งาน → ยืนยันแบบ Warning, เปิดใช้งาน → ยืนยันแบบ Confirm
// (delegation บน #memberTable เพราะ DataTables วาดแถวใหม่ทุกครั้งที่เปลี่ยนหน้า/ค้นหา)
document.getElementById('memberTable').addEventListener('change', function (e) {
    const sw = e.target.closest('input.mb-toggle');
    if (!sw) return;
    const activating = sw.checked;        // สถานะที่ผู้ใช้ต้องการ
    sw.checked = !activating;             // คืนค่าเดิมไว้ก่อน รอยืนยันใน dialog
    const id   = sw.dataset.id;
    const name = sw.dataset.name ? '"' + sw.dataset.name + '"' : '';
    const target = activating
        ? { form: 'mbActivateForm',   name: 'mbActivateName',   modal: 'mbActivateModal' }
        : { form: 'mbDeactivateForm', name: 'mbDeactivateName', modal: 'mbDeactivateModal' };
    document.getElementById(target.form).action = '/admin/members/' + id + '/toggle';
    document.getElementById(target.name).textContent = name;
    bootstrap.Modal.getOrCreateInstance(document.getElementById(target.modal)).show();
});

document.addEventListener('DOMContentLoaded', function () {
    // ── ตาราง DataTables server-side ──
    const grpSel = document.getElementById('mbGroup');
    const dt = NetPass.dataTable('#memberTable', {
        filters: '#mbToolbar',
        action: '#mbAction',
        ajax: {
            url: '<?= site_url('admin/members/data') ?>',
            data: function (d) { d.group = grpSel.value; }
        },
        order: [[0, 'asc']],
        columns: [
            { orderable: true },                        // ชื่อ-สกุล
            { orderable: true },                        // ตำแหน่ง
            { orderable: true },                        // Email
            { orderable: true },                        // Username
            { orderable: true },                        // Role
            { orderable: true },                        // สถานะ
            { orderable: false, className: 'text-end' } // จัดการ
        ]
    });
        window.memberDT = dt;   // ใช้ reload หลัง import
    grpSel.addEventListener('change', function () { dt.ajax.reload(); });
});

// ───────── รูปโปรไฟล์: เลือกไฟล์ → ครอป/ซูมวงกลม → พรีวิว (ใช้ร่วมเพิ่ม/แก้ไข) ─────────
window.addEventListener('load', function () {
    const cropEl  = document.getElementById('mbCropModal');
    const cropImg = document.getElementById('mbCropImg');
    const zoom    = document.getElementById('mbZoom');
    const bsCrop  = new bootstrap.Modal(cropEl);
    let cropper = null, objectUrl = null, prevZoom = 0, activeInput = null, activeBox = null;
    const BADTYPE = <?= json_encode(lang('Profile.avatarBadType')) ?>;

    function wireAvatar(inputId, boxId, hintId) {
        const input = document.getElementById(inputId);
        const box   = document.getElementById(boxId);
        const hint  = document.getElementById(hintId);
        const hintDefault = hint.innerHTML;
        input.addEventListener('change', function () {
            const file = input.files[0];
            if (!file) return;
            if (file.type !== 'image/jpeg' && file.type !== 'image/png') {
                hint.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + BADTYPE;
                hint.classList.add('is-error');
                input.value = '';
                return;
            }
            hint.classList.remove('is-error');
            hint.innerHTML = hintDefault;
            activeInput = input; activeBox = box;
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = URL.createObjectURL(file);
            cropImg.src = objectUrl;
            bsCrop.show();
        });
    }
    wireAvatar('addAvatarInput', 'addAvatarBox', 'addAvatarHint');
    wireAvatar('editAvatarInput', 'editAvatarBox', 'editAvatarHint');

    cropEl.addEventListener('shown.bs.modal', function () {
        prevZoom = 0; zoom.value = 0;
        cropper = new Cropper(cropImg, {
            aspectRatio: 1, viewMode: 1, dragMode: 'move', autoCropArea: 1,
            background: false, guides: false, highlight: false, center: false,
            cropBoxMovable: false, cropBoxResizable: false,
        });
    });
    zoom.addEventListener('input', function () {
        if (!cropper) return;
        const v = parseFloat(zoom.value);
        cropper.zoom(v - prevZoom);
        prevZoom = v;
    });
    cropEl.addEventListener('hidden.bs.modal', function () {
        if (cropper) { cropper.destroy(); cropper = null; }
        // เก็บกวาด backdrop ที่อาจค้างจากการซ้อน modal
        setTimeout(function () {
            if (document.querySelector('.modal.show')) {
                document.body.classList.add('modal-open');
            }
        }, 200);
    });
    document.getElementById('mbCropApply').addEventListener('click', function () {
        if (!cropper || !activeInput) return;
        const type = (activeInput.files[0] && activeInput.files[0].type === 'image/png') ? 'image/png' : 'image/jpeg';
        const canvas = cropper.getCroppedCanvas({ width: 256, height: 256, imageSmoothingQuality: 'high' });
        canvas.toBlob(function (blob) {
            if (blob) {
                const name = 'avatar.' + (type === 'image/png' ? 'png' : 'jpg');
                const dtf = new DataTransfer();
                dtf.items.add(new File([blob], name, { type: type }));
                activeInput.files = dtf.files;
            }
            if (activeBox) { activeBox.innerHTML = '<img src="' + canvas.toDataURL(type) + '" alt="">'; }
            if (activeInput && activeInput.id === 'editAvatarInput') { markEditAvatarChanged(); }
            bsCrop.hide();
        }, type, 0.9);
    });

    // ───────── แถบวัด + เช็คลิสต์รหัสผ่าน + ปุ่มสุ่ม (ฟอร์มเพิ่มสมาชิก) ─────────
    const newPwd     = document.getElementById('addPwd');
    const rulesBox   = document.getElementById('addPwdRules');
    const meter      = document.getElementById('addPwdMeter');
    const meterLabel = document.getElementById('addPwdMeterLabel');
    const STRENGTH = ['', <?= json_encode(lang('Profile.pwdWeak')) ?>, <?= json_encode(lang('Profile.pwdFair')) ?>, <?= json_encode(lang('Profile.pwdGood')) ?>, <?= json_encode(lang('Profile.pwdStrong')) ?>];
    const tests = {
        len:    function (v) { return v.length >= 8; },
        upper:  function (v) { return /[A-Z]/.test(v); },
        lower:  function (v) { return /[a-z]/.test(v); },
        number: function (v) { return /[0-9]/.test(v); },
        symbol: function (v) { return /[^A-Za-z0-9]/.test(v); },
    };
    function evaluate() {
        const v = newPwd.value;
        let passed = 0;
        rulesBox.querySelectorAll('li').forEach(function (li) {
            const ok = tests[li.dataset.rule](v);
            if (ok) passed++;
            li.classList.toggle('ok', ok);
            li.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        });
        let s = 0;
        if (v.length === 0)    s = 0;
        else if (passed <= 2)  s = 1;
        else if (passed === 3) s = 2;
        else if (passed === 4) s = 3;
        else                   s = 4;
        meter.dataset.lvl = s;
        meterLabel.textContent = STRENGTH[s];
    }
    function showPwdUi() { rulesBox.classList.add('show'); meter.classList.add('show'); }
    if (newPwd) {
        newPwd.addEventListener('input', function () {
            if (newPwd.value.length >= 1) { showPwdUi(); }
            else { rulesBox.classList.remove('show'); meter.classList.remove('show'); }
            evaluate();
        });
    }

    // ปุ่มสุ่มรหัสผ่าน — สร้างรหัสแข็งแรง (พิมพ์ใหญ่/เล็ก/เลข/อักขระพิเศษ) แล้วโชว์ให้ admin เห็น/คัดลอก
    function npGenPassword(len) {
        const U = 'ABCDEFGHJKLMNPQRSTUVWXYZ', L = 'abcdefghijkmnpqrstuvwxyz', D = '23456789', S = '!@#$%^&*?-_=+';
        const all = U + L + D + S;
        const pick = function (set) { return set.charAt(Math.floor(Math.random() * set.length)); };
        const out = [pick(U), pick(L), pick(D), pick(S)];
        for (let i = out.length; i < len; i++) { out.push(pick(all)); }
        for (let i = out.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); const t = out[i]; out[i] = out[j]; out[j] = t; }
        return out.join('');
    }
    const randomBtn = document.getElementById('addRandomPwd');
    if (randomBtn && newPwd) {
        randomBtn.addEventListener('click', function () {
            newPwd.value = npGenPassword(12);
            newPwd.type = 'text';   // โชว์ให้เห็น (admin ต้องนำไปแจ้งผู้ใช้)
            const eye = newPwd.parentElement.querySelector('.np-pwd-toggle i');
            if (eye) { eye.className = 'bi bi-eye-slash'; }
            newPwd.classList.remove('np-invalid');
            const sib = newPwd.closest('.np-pwd-wrap').nextElementSibling;
            if (sib && sib.classList.contains('np-field-err')) { sib.remove(); }
            showPwdUi(); evaluate();
        });
    }

    // ───────── reset รหัสผ่าน: meter + checklist + สุ่ม (ใช้ tests/STRENGTH/npGenPassword ร่วม) ─────────
    function setupPwdField(pwd, rules, mt, mtLabel, rndBtn) {
        if (! pwd) { return; }
        function evalP() {
            const v = pwd.value; let passed = 0;
            rules.querySelectorAll('li').forEach(function (li) {
                const ok = tests[li.dataset.rule](v);
                if (ok) passed++;
                li.classList.toggle('ok', ok);
                li.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            });
            const s = v.length === 0 ? 0 : (passed <= 2 ? 1 : (passed === 3 ? 2 : (passed === 4 ? 3 : 4)));
            mt.dataset.lvl = s; mtLabel.textContent = STRENGTH[s];
        }
        function showUi() { rules.classList.add('show'); mt.classList.add('show'); }
        pwd.addEventListener('input', function () {
            if (pwd.value.length >= 1) { showUi(); } else { rules.classList.remove('show'); mt.classList.remove('show'); }
            evalP();
        });
        if (rndBtn) {
            rndBtn.addEventListener('click', function () {
                pwd.value = npGenPassword(12); pwd.type = 'text';
                const eye = pwd.parentElement.querySelector('.np-pwd-toggle i');
                if (eye) { eye.className = 'bi bi-eye-slash'; }
                pwd.classList.remove('np-invalid');
                const sib = pwd.closest('.np-pwd-wrap').nextElementSibling;
                if (sib && sib.classList.contains('np-field-err')) { sib.remove(); }
                showUi(); evalP();
            });
        }
    }
    setupPwdField(document.getElementById('rsPwd'), document.getElementById('rsPwdRules'),
                  document.getElementById('rsPwdMeter'), document.getElementById('rsPwdMeterLabel'),
                  document.getElementById('rsRandomPwd'));
    // ฟอร์มรีเซ็ต: ตรวจ inline แล้ว submit ตรง
    document.getElementById('mbResetForm').addEventListener('submit', function (e) {
        if (! npValidateForm(this)) { e.preventDefault(); }
    });

    // ───────── สรุปข้อมูลก่อนยืนยันสร้างสมาชิก (เปิดเดี่ยว — ปิดฟอร์มก่อน ไม่ให้ backdrop ซ้อนมืด) ─────────
    const addForm    = document.getElementById('addForm');
    const addModalEl = document.getElementById('addModal');
    const bsAdd      = bootstrap.Modal.getOrCreateInstance(addModalEl);
    const sumModalEl = document.getElementById('mbSummaryModal');
    const bsSum      = new bootstrap.Modal(sumModalEl);
    const roleSel    = addForm.querySelector('select[name="role"]');
    const FORCE_NOTE = <?= json_encode(lang('Member.forceChangeNote')) ?>;
    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function buildSummary() {
        const g = function (n) { const el = addForm.querySelector('[name="' + n + '"]'); return el ? el.value.trim() : ''; };
        const rows = [
            [<?= json_encode(lang('Member.colName')) ?>, (g('firstname') + ' ' + g('lastname')).trim()],
            [<?= json_encode(lang('Member.email')) ?>, g('email') || '—'],
            [<?= json_encode(lang('Member.position')) ?>, g('position') || '—'],
            [<?= json_encode(lang('Member.role')) ?>, roleSel.options[roleSel.selectedIndex].text],
        ];
        document.getElementById('sumRows').innerHTML = rows.map(function (r) {
            return '<div class="np-sum-row"><span class="np-sum-k">' + r[0] + '</span><span class="np-sum-v">' + escapeHtml(r[1]) + '</span></div>';
        }).join('');
        document.getElementById('sumUser').textContent = g('username');
        document.getElementById('sumPass').textContent = g('password');
        const note = document.getElementById('sumForceNote');
        if (document.getElementById('addForceChange').checked) {
            note.style.display = ''; note.innerHTML = '<i class="bi bi-shield-lock"></i> ' + FORCE_NOTE;
        } else { note.style.display = 'none'; note.innerHTML = ''; }
    }
    let addConfirmed = false;
    addForm.addEventListener('submit', function (e) {
        if (addConfirmed) { return; }       // ยืนยันในสรุปแล้ว → submit จริง
        e.preventDefault();
        if (! npValidateForm(addForm)) { return; }
        buildSummary();
        bsSum.show();                        // ซ้อนทับฟอร์ม (backdrop ชั้นบนโปร่งใส ไม่มืดซ้อน)
    });
    // Back = ปิดสรุป กลับไปฟอร์มเดิมที่ยังเปิดอยู่ด้านล่าง (ใช้ data-bs-dismiss)
    // OK = สร้างจริง
    document.getElementById('mbSummaryConfirm').addEventListener('click', function () {
        addConfirmed = true;
        addForm.submit();
    });
    sumModalEl.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const val = document.getElementById(btn.dataset.copy).textContent;
            if (! val) { return; }
            try { await navigator.clipboard.writeText(val); } catch (_) {}
            const icon = btn.querySelector('i'); const prev = icon.className;
            icon.className = 'bi bi-check-lg'; setTimeout(function () { icon.className = prev; }, 1300);
        });
    });

    // ───────── แก้ไขสมาชิก: ตรวจการเปลี่ยนแปลง + review ก่อนบันทึก ─────────
    const editFormEl   = document.getElementById('editForm');
    const editModalEl  = document.getElementById('editModal');
    const editSaveBtn  = document.getElementById('editSaveBtn');
    const bsEditReview = new bootstrap.Modal(document.getElementById('mbEditReviewModal'));
    const ROLE_LBL  = <?= json_encode(lang('Member.role')) ?>;
    const ADMIN_LBL = <?= json_encode(lang('Member.roleAdmin')) ?>;
    const USER_LBL  = <?= json_encode(lang('Member.roleUser')) ?>;
    const PHOTO_LBL = <?= json_encode(lang('Profile.photoTitle')) ?>;
    const NOCHANGE  = <?= json_encode(lang('Member.editNoChange')) ?>;
    let editSnapshot = null, editAvatarChanged = false, editConfirmed = false;

    function markEditAvatarChanged() { editAvatarChanged = true; updateEditSaveState(); }
    function editVals() {
        return {
            email:     editFormEl.email.value.trim(),
            firstname: editFormEl.firstname.value.trim(),
            lastname:  editFormEl.lastname.value.trim(),
            position:  editFormEl.position.value.trim(),
            role:      editFormEl.role.value,
        };
    }
    function editChanged() {
        if (! editSnapshot) { return false; }
        if (editAvatarChanged) { return true; }
        const v = editVals();
        return v.email !== editSnapshot.email || v.firstname !== editSnapshot.firstname
            || v.lastname !== editSnapshot.lastname || v.position !== editSnapshot.position
            || v.role !== editSnapshot.role;
    }
    function updateEditSaveState() { editSaveBtn.disabled = ! editChanged(); }

    // snapshot ตอน modal แสดงเต็มที่ (หลังเติมค่า + Tom Select set แล้ว) → ปุ่มเริ่มเป็น disable
    editModalEl.addEventListener('shown.bs.modal', function () {
        editSnapshot = editVals();
        editAvatarChanged = false;
        editConfirmed = false;
        updateEditSaveState();
    });
    editFormEl.addEventListener('input', updateEditSaveState);
    editFormEl.addEventListener('change', updateEditSaveState);

    function roleLabel(v) { return v === 'admin' ? ADMIN_LBL : USER_LBL; }
    function changeRow(label, from, to) {
        return '<div class="np-sum-row"><span class="np-sum-k">' + label + '</span>'
            + '<span class="np-sum-v"><span class="np-chg-old">' + escapeHtml(from) + '</span> '
            + '<i class="bi bi-arrow-right np-chg-arrow"></i> <span class="np-chg-new">' + escapeHtml(to) + '</span></span></div>';
    }
    function buildEditReview() {
        const v = editVals(); let html = '';
        if (v.email !== editSnapshot.email)         { html += changeRow(<?= json_encode(lang('Member.email')) ?>, editSnapshot.email || '—', v.email || '—'); }
        if (v.firstname !== editSnapshot.firstname) { html += changeRow(<?= json_encode(lang('Member.firstName')) ?>, editSnapshot.firstname || '—', v.firstname || '—'); }
        if (v.lastname !== editSnapshot.lastname)   { html += changeRow(<?= json_encode(lang('Member.lastName')) ?>, editSnapshot.lastname || '—', v.lastname || '—'); }
        if (v.position !== editSnapshot.position)   { html += changeRow(<?= json_encode(lang('Member.position')) ?>, editSnapshot.position || '—', v.position || '—'); }
        if (v.role !== editSnapshot.role)           { html += changeRow(ROLE_LBL, roleLabel(editSnapshot.role), roleLabel(v.role)); }
        if (editAvatarChanged)                      { html += changeRow(PHOTO_LBL, '—', <?= json_encode(lang('Profile.uploadNew')) ?>); }
        document.getElementById('editReviewRows').innerHTML = html || ('<p class="text-muted mb-0">' + NOCHANGE + '</p>');
    }
    editFormEl.addEventListener('submit', function (e) {
        if (editConfirmed) { return; }
        e.preventDefault();
        if (! npValidateForm(editFormEl)) { return; }
        if (! editChanged()) { return; }     // ไม่มีการเปลี่ยน → ไม่ทำอะไร (ปุ่ม disable อยู่แล้ว)
        buildEditReview();
        bsEditReview.show();                 // ซ้อนทับ edit modal (backdrop โปร่งใส)
    });
    document.getElementById('mbEditReviewConfirm').addEventListener('click', function () {
        editConfirmed = true;
        editFormEl.submit();
    });

    // ปุ่ม eye เปิด/ปิดดูรหัสผ่าน
    document.querySelectorAll('.np-modal-member .np-pwd-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const inp = btn.parentElement.querySelector('input');
            const reveal = inp.type === 'password';
            inp.type = reveal ? 'text' : 'password';
            btn.querySelector('i').className = reveal ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    // เปิด modal เดิมกลับมาเมื่อ validation ฝั่ง server ไม่ผ่าน (fallback)
<?php if ($isAddErr): ?>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('addModal')).show();
<?php elseif ($isEditErr): ?>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
<?php endif ?>
});

// เริ่มพิมพ์ในช่อง → ลบกรอบแดง + ข้อความ error ทันที
document.querySelectorAll('.np-modal-member .form-control').forEach(function (inp) {
    inp.addEventListener('input', function () {
        inp.classList.remove('np-invalid');
        const wrap = inp.closest('.np-pwd-wrap') || inp;
        const msg = wrap.nextElementSibling;
        if (msg && msg.classList.contains('np-field-err')) { msg.remove(); }
    });
});

// ── client-side validation: เตือน inline ในตัว modal ไม่ปิด/รีโหลด ──
function npAddErr(inp, message) {
    inp.classList.add('np-invalid');
    const anchor = inp.closest('.np-pwd-wrap') || inp;
    let sib = anchor.nextElementSibling;
    if (sib && sib.classList.contains('np-field-err')) sib.remove();
    const p = document.createElement('p');
    p.className = 'np-field-err';
    p.innerHTML = '<i class="bi bi-info-circle-fill"></i> ' + message;
    anchor.insertAdjacentElement('afterend', p);
}
function npValidateForm(form) {
    let ok = true;
    // ล้าง error เดิม
    form.querySelectorAll('.np-invalid').forEach(function (i) { i.classList.remove('np-invalid'); });
    form.querySelectorAll('.np-field-err').forEach(function (m) { if (!m.dataset.server) m.remove(); });
    // ช่องบังคับว่าง
    form.querySelectorAll('[data-req]').forEach(function (inp) {
        if (inp.value.trim() === '') { ok = false; npAddErr(inp, inp.dataset.reqmsg); }
    });
    // อีเมล: ไม่บังคับ แต่ถ้ากรอกต้องถูก format
    const em = form.querySelector('input[name="email"]');
    if (em && em.value.trim() !== '' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(em.value.trim())) {
        ok = false; npAddErr(em, em.dataset.fmtmsg);
    }
    // รหัสผ่าน: ความแข็งแรง (ช่องใดก็ตามที่มี data-weakmsg — ฟอร์มเพิ่ม/รีเซ็ต)
    const pwd = form.querySelector('[data-weakmsg]');
    if (pwd && pwd.value.trim() !== '') {
        const strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(pwd.value);
        if (!strong) { ok = false; npAddErr(pwd, pwd.dataset.weakmsg); }
    }
    if (!ok) { const first = form.querySelector('.np-invalid'); if (first) first.focus(); }
    return ok;
}
</script>
<script>
// ───────── นำเข้าสมาชิก: upload → JSON → result modal → reload ตาราง ─────────
(function () {
    const form = document.getElementById('importForm');
    if (!form) return;
    const btn       = document.getElementById('importSubmit');
    const fileErr   = document.getElementById('importErr');
    const fileInput = document.getElementById('importFile');
    const drop      = document.getElementById('importDrop');
    const chip      = document.getElementById('importFileChip');
    const chipName  = document.getElementById('importFileName');
    const chipSize  = document.getElementById('importFileSize');
    const tokenName = '<?= csrf_token() ?>';
    const NO_FILE   = '<?= esc(lang('Member.importNoFile'), 'js') ?>';
    const BAD_FILE  = '<?= esc(lang('Member.importBadFile'), 'js') ?>';
    const locale    = document.documentElement.lang === 'th' ? 'th' : 'en';
    let failDT = null;

    function showErr(msg) {
        fileErr.querySelector('span').textContent = msg;
        fileErr.classList.remove('d-none');
    }
    function setLoading(on) {
        btn.disabled = on;
        btn.querySelector('.np-btn-label').classList.toggle('d-none', on);
        btn.querySelector('.spinner-border').classList.toggle('d-none', !on);
    }
    function updateCsrf(token) {
        if (!token) return;
        const input = form.querySelector('input[name="' + tokenName + '"]');
        if (input) input.value = token;
    }
    // bytes → อ่านง่าย (KB/MB)
    function fmtSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
    // เลือกไฟล์แล้ว → โชว์ file chip แทน dropzone
    function reflectFile() {
        const f = fileInput.files && fileInput.files[0];
        if (f) {
            chipName.textContent = f.name;
            chipSize.textContent = fmtSize(f.size);
            drop.classList.add('d-none');
            chip.classList.remove('d-none');
            fileErr.classList.add('d-none');
        } else {
            drop.classList.remove('d-none');
            chip.classList.add('d-none');
        }
    }
    function resetDrop() {
        fileInput.value = '';
        reflectFile();
        fileErr.classList.add('d-none');
    }

    // ── dropzone interaction ──
    // คลิก dropzone → เปิด file picker (input ซ่อนอยู่) / ลากวาง → ดัก drop เองแล้วยัด dataTransfer.files เข้า input
    drop.addEventListener('click', function (e) {
        // กัน re-entrancy: fileInput.click() สร้าง click ที่ bubble กลับมาที่ .np-drop → ห้ามเปิด picker ซ้ำ
        if (e.target === fileInput) return;
        fileInput.click();
    });
    fileInput.addEventListener('change', reflectFile);
    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); drop.classList.add('is-drag'); });
    });
    drop.addEventListener('dragleave', function (e) {
        if (e.target === drop) drop.classList.remove('is-drag');   // เอาไฮไลต์ออกเฉพาะตอนออกนอกกล่องจริง
    });
    drop.addEventListener('drop', function (e) {
        e.preventDefault(); e.stopPropagation();
        drop.classList.remove('is-drag');
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;   // ใส่ไฟล์ที่ลากเข้า input โดยตรง
            reflectFile();
        }
    });
    document.getElementById('importFileRemove').addEventListener('click', resetDrop);
    // เปิด modal ใหม่ทุกครั้ง → ล้างสถานะ dropzone
    document.getElementById('importModal').addEventListener('hidden.bs.modal', resetDrop);

    // ── ตารางแถวพลาดใน result (DataTables client-side, 10/หน้า) ──
    function renderFailures(failures) {
        const wrap   = document.getElementById('resFailWrap');
        const dialog = document.querySelector('#resultModal .modal-dialog');
        const rows = (failures || []).map(function (f) { return [f.row, f.username || '—', f.reason]; });
        if (!rows.length) {
            wrap.classList.add('d-none');
            dialog.classList.remove('is-wide');   // สำเร็จล้วน → กล่องแคบสมดุล (เฉพาะวง %)
            if (failDT) { failDT.clear().draw(); }
            return;
        }
        dialog.classList.add('is-wide');          // มีแถวพลาด → ขยายกล่องให้ตารางพอดี
        if (failDT) {
            failDT.clear().rows.add(rows).draw();
        } else {
            failDT = new DataTable('#resFailTable', {
                data: rows,
                paging: true, pageLength: 10, lengthChange: false,
                searching: false, ordering: false, info: true, autoWidth: false,
                columns: [
                    { className: 'text-center np-fail-row', width: '56px' },   // แถวที่
                    { className: 'np-fail-user', width: '34%' },               // username (mono)
                    { className: 'np-fail-reason' }                            // เหตุผล (ตัดบรรทัดได้)
                ],
                language: locale === 'th'
                    ? { info: 'แสดง _START_–_END_ จาก _TOTAL_', infoEmpty: 'ทั้งหมด 0', emptyTable: 'ไม่มีข้อมูล', paginate: { first: '«', previous: '‹', next: '›', last: '»' } }
                    : { info: 'Showing _START_–_END_ of _TOTAL_', infoEmpty: '0 items', emptyTable: 'No data', paginate: { first: '«', previous: '‹', next: '›', last: '»' } }
            });
        }
        wrap.classList.remove('d-none');
    }
    // คำนวณความกว้างคอลัมน์ใหม่หลัง modal โชว์ (กัน layout เพี้ยนเพราะ init ตอนซ่อน)
    document.getElementById('resultModal').addEventListener('shown.bs.modal', function () {
        if (failDT) { failDT.columns.adjust(); }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        fileErr.classList.add('d-none');
        if (!fileInput.files || !fileInput.files.length) { showErr(NO_FILE); return; }
        setLoading(true);
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            updateCsrf(json.csrf);
            if (json.error) { showErr(json.error); return; }

            // เติม result modal
            document.getElementById('resPercent').textContent = json.percent + '%';
            document.getElementById('resRing').style.setProperty('--pct', json.percent);
            document.getElementById('resSuccess').textContent = json.success;
            document.getElementById('resFail').textContent = json.failed;
            renderFailures(json.failures);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('importModal')).hide();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('resultModal')).show();
            resetDrop();
            if (window.memberDT) window.memberDT.ajax.reload(null, false);
        })
        .catch(function () { showErr(BAD_FILE); })
        .finally(function () { setLoading(false); });
    });
})();
</script>
<?= $this->endSection() ?>
