<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$isEn    = service('request')->getLocale() === 'en';
$locName = static fn ($loc) => $isEn ? (($loc['name_en'] ?? '') ?: $loc['name']) : (($loc['name'] ?? '') ?: $loc['name_en']);

// inline validation ของฟอร์ม Location: เปิด modal เดิมกลับมาพร้อม error ใต้ช่อง
$locErrors = (array) (session('loc_errors') ?? []);
$locForm   = session('loc_form');        // 'add' | 'edit' | null
$locEditId = session('loc_edit_id');
$isAddErr  = $locForm === 'add';
$isEditErr = $locForm === 'edit';
$invCls  = static fn (bool $enabled, string $field) => ($enabled && isset($locErrors[$field])) ? ' np-invalid' : '';
$errLine = static fn (bool $enabled, string $field) => ($enabled && ! empty($locErrors[$field]))
    ? '<p class="np-field-err"><i class="bi bi-info-circle-fill"></i> ' . esc($locErrors[$field]) . '</p>' : '';
$oldVal  = static fn (bool $enabled, string $field) => $enabled ? esc(old($field)) : '';
?>

<div class="np-card np-dt">
    <!-- ปุ่มนำเข้า voucher -->
    <div id="poolAction" class="d-flex gap-2">
        <button class="btn btn-np-outline" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i><?= lang('Location.addTitle') ?>
        </button>
        <button class="btn btn-np" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-box-arrow-in-down me-1"></i><?= lang('Pool.import') ?>
        </button>
    </div>

    <table id="poolTable" class="np-table align-middle" style="width:100%">
        <thead>
            <tr>
                <th><?= lang('Pool.colLocation') ?></th>
                <th><?= lang('Pool.remaining') ?></th>
                <th><?= lang('Pool.issued') ?></th>
                <th><?= lang('Pool.total') ?></th>
                <th class="text-end"><?= lang('Pool.colActions') ?></th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>

<?= $this->section('modals') ?>
<!-- Import modal -->
<div class="modal fade np-modal" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="poolImportForm" method="post" action="<?= site_url('admin/pool/import') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico"><i class="bi bi-box-arrow-in-down"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Pool.importTitle') ?></h5>
                    <p><?= lang('Pool.importSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6 np-field mb-0">
                        <label class="form-label" for="poolImportLoc"><?= lang('Pool.selectLocation') ?></label>
                        <select name="location_id" id="poolImportLoc" class="form-select" required>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= $location['id'] ?>"><?= esc($locName($location)) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-6 np-field mb-0">
                        <label class="form-label" for="poolImportDur"><?= lang('Pool.selectDuration') ?></label>
                        <select name="duration" id="poolImportDur" class="form-select" required>
                            <?php foreach ($durations as $key => $durationItem): ?>
                                <option value="<?= $key ?>"><?= esc($isEn ? $durationItem['label_en'] : $durationItem['label']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3 mb-2">
                    <a href="<?= site_url('admin/pool/import/template') ?>" download="voucher-template.csv" class="np-tpl-chip"><i class="bi bi-download"></i><?= lang('Pool.importDownloadTpl') ?></a>
                </div>

                <div class="np-drop" id="poolImportDrop" tabindex="0" role="button" aria-label="<?= esc(lang('Pool.importChooseFile'), 'attr') ?>">
                    <input type="file" name="file" id="poolImportFile" accept=".csv">
                    <div class="np-drop-ico"><i class="bi bi-filetype-csv"></i></div>
                    <div class="np-drop-title"><?= lang('Pool.importDropTitle') ?> <span class="np-drop-browse"><?= lang('Pool.importDropBrowse') ?></span></div>
                    <div class="np-drop-meta"><?= lang('Pool.importDropMeta') ?></div>
                </div>

                <div class="np-drop-file d-none" id="poolImportChip">
                    <span class="fico"><i class="bi bi-filetype-csv"></i></span>
                    <div class="meta">
                        <div class="name" id="poolImportName">—</div>
                        <div class="size" id="poolImportSize"></div>
                    </div>
                    <button type="button" class="np-drop-remove" id="poolImportRemove" aria-label="<?= esc(lang('Pool.importRemoveFile'), 'attr') ?>"><i class="bi bi-x-lg"></i></button>
                </div>

                <p class="np-field-err d-none mt-2" id="poolImportErr"><i class="bi bi-info-circle-fill"></i> <span></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" id="poolImportSubmit" class="btn btn-np">
                    <span class="np-btn-label"><i class="bi bi-box-arrow-in-down me-1"></i><?= lang('Pool.import') ?></span>
                    <span class="spinner-border spinner-border-sm text-white d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: สรุปผลนำเข้า voucher -->
<div class="modal fade np-modal np-modal-confirm" id="poolResultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="np-modal-ico is-confirm"><i class="bi bi-clipboard-check"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Pool.imported') ?></h5>
                    <p><?= lang('Pool.importSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div style="width:56px;height:56px;border-radius:50%;background:#e6f6ef;color:#0EA66B;display:inline-flex;align-items:center;justify-content:center;font-size:28px"><i class="bi bi-check-lg"></i></div>
                    <p class="fw-bold mt-2 mb-0" id="poolResCount">—</p>
                </div>
                <div class="np-confirm-box">
                    <div class="np-confirm-row">
                        <i class="bi bi-geo-alt-fill"></i>
                        <div>
                            <div class="np-confirm-lbl"><?= lang('Pool.colLocation') ?></div>
                            <div class="np-confirm-val" id="poolResLoc">—</div>
                        </div>
                    </div>
                    <div class="np-confirm-row">
                        <i class="bi bi-clock-fill"></i>
                        <div>
                            <div class="np-confirm-lbl"><?= lang('Voucher.duration') ?></div>
                            <div class="np-confirm-val" id="poolResDur">—</div>
                        </div>
                    </div>
                    <div class="np-confirm-row">
                        <i class="bi bi-router"></i>
                        <div>
                            <div class="np-confirm-lbl">SSID</div>
                            <div class="np-confirm-val font-mono" id="poolResSsid">—</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-np" data-bs-dismiss="modal"><?= lang('Voucher.btnClose') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: เพิ่มพื้นที่ -->
<div class="modal fade np-modal" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('admin/locations') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico"><i class="bi bi-geo-alt-fill"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Location.addTitle') ?></h5>
                    <p><?= lang('Location.addSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="np-field">
                    <label class="form-label" for="addNameEn"><?= lang('Location.nameEn') ?> <span class="np-req">*</span></label>
                    <input type="text" name="name_en" id="addNameEn" class="form-control<?= $invCls($isAddErr, 'name_en') ?>" maxlength="150"
                           placeholder="<?= esc(lang('Location.phNameEn'), 'attr') ?>"
                           data-req data-reqmsg="<?= esc(lang('Location.errNameEnRequired'), 'attr') ?>"
                           value="<?= $oldVal($isAddErr, 'name_en') ?>">
                    <?= $errLine($isAddErr, 'name_en') ?>
                </div>
                <div class="np-field">
                    <label class="form-label" for="addName"><?= lang('Location.name') ?></label>
                    <input type="text" name="name" id="addName" autocomplete="off" class="form-control<?= $invCls($isAddErr, 'name') ?>" maxlength="150"
                           placeholder="<?= esc(lang('Location.phName'), 'attr') ?>"
                           value="<?= $oldVal($isAddErr, 'name') ?>">
                    <?= $errLine($isAddErr, 'name') ?>
                </div>
                <div class="np-field mb-0">
                    <label class="form-label" for="addSsid"><?= lang('Location.ssid') ?> <span class="np-req">*</span></label>
                    <input type="text" name="ssid" id="addSsid" class="form-control font-mono<?= $invCls($isAddErr, 'ssid') ?>" maxlength="100"
                           placeholder="<?= esc(lang('Location.phSsid'), 'attr') ?>"
                           data-req data-reqmsg="<?= esc(lang('Location.errSsidRequired'), 'attr') ?>"
                           value="<?= $oldVal($isAddErr, 'ssid') ?>">
                    <?= $errLine($isAddErr, 'ssid') ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="btn btn-np"><i class="bi bi-check-lg"></i><?= lang('Common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: แก้ไขพื้นที่ -->
<div class="modal fade np-modal" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="editForm" action="<?= $isEditErr ? site_url('admin/locations/' . $locEditId . '/update') : '' ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico"><i class="bi bi-geo-alt-fill"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Location.editTitle') ?></h5>
                    <p><?= lang('Location.editSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="np-field">
                    <label class="form-label" for="editNameEn"><?= lang('Location.nameEn') ?> <span class="np-req">*</span></label>
                    <input type="text" name="name_en" id="editNameEn" class="form-control<?= $invCls($isEditErr, 'name_en') ?>" maxlength="150"
                           placeholder="<?= esc(lang('Location.phNameEn'), 'attr') ?>"
                           data-req data-reqmsg="<?= esc(lang('Location.errNameEnRequired'), 'attr') ?>"
                           value="<?= $oldVal($isEditErr, 'name_en') ?>">
                    <?= $errLine($isEditErr, 'name_en') ?>
                </div>
                <div class="np-field">
                    <label class="form-label" for="editName"><?= lang('Location.name') ?></label>
                    <input type="text" name="name" id="editName" autocomplete="off" class="form-control<?= $invCls($isEditErr, 'name') ?>" maxlength="150"
                           placeholder="<?= esc(lang('Location.phName'), 'attr') ?>"
                           value="<?= $oldVal($isEditErr, 'name') ?>">
                    <?= $errLine($isEditErr, 'name') ?>
                </div>
                <div class="np-field mb-0">
                    <label class="form-label" for="editSsid"><?= lang('Location.ssid') ?> <span class="np-req">*</span></label>
                    <input type="text" name="ssid" id="editSsid" class="form-control font-mono<?= $invCls($isEditErr, 'ssid') ?>" maxlength="100"
                           placeholder="<?= esc(lang('Location.phSsid'), 'attr') ?>"
                           data-req data-reqmsg="<?= esc(lang('Location.errSsidRequired'), 'attr') ?>"
                           value="<?= $oldVal($isEditErr, 'ssid') ?>">
                    <?= $errLine($isEditErr, 'ssid') ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="btn btn-np" id="editSaveBtn"><i class="bi bi-check-lg"></i><?= lang('Common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ยืนยันการแก้ไขพื้นที่ ก่อน submit -->
<div class="modal fade np-dialog-modal" id="locEditConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content np-dialog">
            <div class="dlg-head">
                <span class="dlg-ico is-confirm"><i class="bi bi-pencil-square"></i></span>
                <div class="dlg-htext">
                    <h5><?= lang('Pool.editConfirmTitle') ?></h5>
                    <p><?= lang('Pool.editConfirmSub') ?></p>
                </div>
                <button type="button" class="dlg-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="dlg-body">
                <p><?= lang('Pool.editConfirmBody') ?></p>
                <!-- ตารางเทียบค่าเดิม → ค่าใหม่ -->
                <table class="dlg-cmp-table">
                    <thead>
                        <tr><th><?= lang('Common.changeField') ?></th><th><?= lang('Common.changeOld') ?></th><th><?= lang('Common.changeNew') ?></th></tr>
                    </thead>
                    <tbody id="locEditDiffBody"></tbody>
                </table>
            </div>
            <div class="dlg-foot">
                <button type="button" class="dlg-btn dlg-btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="button" class="dlg-btn dlg-btn-confirm" id="locEditConfirmBtn"><i class="bi bi-check-lg"></i> <?= lang('Pool.editSaveChanges') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: ยืนยันลบพื้นที่ (delete) -->
<div class="modal fade np-dialog-modal" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content np-dialog" method="post" id="deleteForm" action="">
            <?= csrf_field() ?>
            <div class="dlg-head">
                <span class="dlg-ico is-delete"><i class="bi bi-trash3-fill"></i></span>
                <div class="dlg-htext">
                    <h5><?= lang('Location.deleteTitle') ?></h5>
                    <p><?= lang('Location.deleteSub') ?></p>
                </div>
                <button type="button" class="dlg-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                <div class="dlg-callout is-delete">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <span><?= lang('Location.deleteImpact') ?></span>
                </div>
            </div>
            <div class="dlg-body is-centered">
                <div class="dlg-warn-ico is-delete"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <p><?= lang('Location.confirmDelete') ?></p>
                <p class="dlg-target" id="deleteLocName"></p>
            </div>
            <div class="dlg-foot">
                <button type="button" class="dlg-btn dlg-btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="dlg-btn dlg-btn-delete"><i class="bi bi-trash"></i> <?= lang('Common.delete') ?></button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php
// ค่าจาก server → data island
$npPool = [
    'dataUrl'   => site_url('admin/pool/data'),
    'csrfName'  => csrf_token(),
    'badFile'   => lang('Pool.importBadFile'),
    'countUnit' => lang('Voucher.confirmCountUnit'),
    'openModal' => $isAddErr ? 'add' : ($isEditErr ? 'edit' : ''),
    'i18n'      => [
        'nameEn' => lang('Location.nameEn'),
        'name'   => lang('Location.name'),
        'ssid'   => lang('Location.ssid'),
    ],
];
$poolJs = FCPATH . 'assets/js/pool/index.js';
?>
<script type="application/json" id="np-pool-data"><?= json_encode($npPool, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= base_url('assets/js/pool/index.js') ?>?v=<?= is_file($poolJs) ? filemtime($poolJs) : '1' ?>"></script>
<?= $this->endSection() ?>
