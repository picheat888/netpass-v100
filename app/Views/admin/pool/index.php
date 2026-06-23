<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$isEn    = service('request')->getLocale() === 'en';
$locName = static fn ($loc) => $isEn ? (($loc['name_en'] ?? '') ?: $loc['name']) : $loc['name'];

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
    <!-- ปุ่มนำเข้า voucher (DataTables วางไว้ใน toolbar ขวา) -->
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
                        <label class="form-label"><?= lang('Pool.selectLocation') ?></label>
                        <select name="location_id" class="form-select" required>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= $location['id'] ?>"><?= esc($locName($location)) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-6 np-field mb-0">
                        <label class="form-label"><?= lang('Pool.selectDuration') ?></label>
                        <select name="duration" class="form-select" required>
                            <?php foreach ($durations as $key => $durationItem): ?>
                                <option value="<?= $key ?>"><?= esc($isEn ? $durationItem['label_en'] : $durationItem['label']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3 mb-2">
                    <a href="<?= site_url('admin/pool/import/template') ?>" class="np-tpl-chip"><i class="bi bi-download"></i><?= lang('Pool.importDownloadTpl') ?></a>
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
                    <label class="form-label"><?= lang('Location.nameEn') ?> <span class="np-req">*</span></label>
                    <input type="text" name="name_en" class="form-control<?= $invCls($isAddErr, 'name_en') ?>" maxlength="150"
                           placeholder="<?= esc(lang('Location.phNameEn'), 'attr') ?>"
                           data-req data-reqmsg="<?= esc(lang('Location.errNameEnRequired'), 'attr') ?>"
                           value="<?= $oldVal($isAddErr, 'name_en') ?>">
                    <?= $errLine($isAddErr, 'name_en') ?>
                </div>
                <div class="np-field">
                    <label class="form-label"><?= lang('Location.name') ?></label>
                    <input type="text" name="name" class="form-control<?= $invCls($isAddErr, 'name') ?>" maxlength="150"
                           placeholder="<?= esc(lang('Location.phName'), 'attr') ?>"
                           value="<?= $oldVal($isAddErr, 'name') ?>">
                    <?= $errLine($isAddErr, 'name') ?>
                </div>
                <div class="np-field mb-0">
                    <label class="form-label"><?= lang('Location.ssid') ?> <span class="np-req">*</span></label>
                    <input type="text" name="ssid" class="form-control font-mono<?= $invCls($isAddErr, 'ssid') ?>" maxlength="100"
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
                    <label class="form-label"><?= lang('Location.nameEn') ?> <span class="np-req">*</span></label>
                    <input type="text" name="name_en" id="editNameEn" class="form-control<?= $invCls($isEditErr, 'name_en') ?>" maxlength="150"
                           placeholder="<?= esc(lang('Location.phNameEn'), 'attr') ?>"
                           data-req data-reqmsg="<?= esc(lang('Location.errNameEnRequired'), 'attr') ?>"
                           value="<?= $oldVal($isEditErr, 'name_en') ?>">
                    <?= $errLine($isEditErr, 'name_en') ?>
                </div>
                <div class="np-field">
                    <label class="form-label"><?= lang('Location.name') ?></label>
                    <input type="text" name="name" id="editName" class="form-control<?= $invCls($isEditErr, 'name') ?>" maxlength="150"
                           placeholder="<?= esc(lang('Location.phName'), 'attr') ?>"
                           value="<?= $oldVal($isEditErr, 'name') ?>">
                    <?= $errLine($isEditErr, 'name') ?>
                </div>
                <div class="np-field mb-0">
                    <label class="form-label"><?= lang('Location.ssid') ?> <span class="np-req">*</span></label>
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

<!-- Modal: ยืนยันลบพื้นที่ -->
<div class="modal fade np-modal np-modal-confirm" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="deleteForm" action="">
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico is-danger"><i class="bi bi-trash3-fill"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Location.deleteTitle') ?></h5>
                    <p><?= lang('Location.deleteSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <div class="np-callout is-danger">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <span><?= lang('Location.deleteImpact') ?></span>
                </div>
            </div>
            <div class="modal-body">
                <div class="text-center mb-2">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:2.75rem;color:var(--np-red)"></i>
                </div>
                <p class="mb-0 text-center"><?= lang('Location.confirmDelete') ?></p>
                <p class="fw-semibold mt-1 mb-0 text-center" id="deleteLocName"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i><?= lang('Common.delete') ?></button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// ตาราง DataTables server-side (สรุปสต็อกแยกพื้นที่)
document.addEventListener('DOMContentLoaded', function () {
    NetPass.dataTable('#poolTable', {
        action: '#poolAction',
        ajax: { url: '<?= site_url('admin/pool/data') ?>' },
        order: [[0, 'asc']],
        columns: [
            { orderable: true },  // พื้นที่
            { orderable: true },  // คงเหลือ
            { orderable: true },  // จ่ายแล้ว
            { orderable: true },  // รวม
            { orderable: false }  // ดู
        ]
    });

    // นำเข้า CSV: dropzone + ส่ง AJAX (all-or-nothing) สำเร็จ → reload หน้า, ผิด → โชว์ error inline
    const impForm = document.getElementById('poolImportForm');
    if (impForm) {
        const drop   = document.getElementById('poolImportDrop');
        const input  = document.getElementById('poolImportFile');
        const chip   = document.getElementById('poolImportChip');
        const nameEl = document.getElementById('poolImportName');
        const sizeEl = document.getElementById('poolImportSize');
        const remove = document.getElementById('poolImportRemove');
        const errBox = document.getElementById('poolImportErr');
        const submit = document.getElementById('poolImportSubmit');
        const csrfField = impForm.querySelector('input[name="<?= csrf_token() ?>"]');
        const BAD    = <?= json_encode(lang('Pool.importBadFile')) ?>;

        const showErr = function (msg) { errBox.querySelector('span').textContent = msg; errBox.classList.remove('d-none'); };
        const clearErr = function () { errBox.classList.add('d-none'); };
        const fmtSize = function (b) { return b < 1024 ? b + ' B' : (b / 1024).toFixed(1) + ' KB'; };

        const setFile = function (file) {
            if (! file) { return; }
            if (! file.name.toLowerCase().endsWith('.csv') || file.size > 2 * 1024 * 1024) {
                input.value = ''; showErr(BAD); return;
            }
            clearErr();
            nameEl.textContent = file.name;
            sizeEl.textContent = fmtSize(file.size);
            drop.classList.add('d-none');
            chip.classList.remove('d-none');
        };

        input.addEventListener('change', function () { setFile(input.files[0]); });
        drop.addEventListener('click', function () { input.click(); });
        drop.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
        ['dragover', 'dragenter'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-drag'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-drag'); }); });
        drop.addEventListener('drop', function (e) { const f = e.dataTransfer.files[0]; if (f) { input.files = e.dataTransfer.files; setFile(f); } });
        remove.addEventListener('click', function () { input.value = ''; chip.classList.add('d-none'); drop.classList.remove('d-none'); clearErr(); });

        impForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearErr();
            if (! input.files[0]) { showErr(BAD); return; }
            submit.querySelector('.np-btn-label').classList.add('d-none');
            submit.querySelector('.spinner-border').classList.remove('d-none');
            submit.disabled = true;
            try {
                const res  = await fetch(impForm.action, { method: 'POST', body: new FormData(impForm), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.csrf && csrfField) { csrfField.value = data.csrf; }
                if (data.ok) { window.location.reload(); return; }
                showErr(data.error || BAD);
            } catch (_) {
                showErr(BAD);
            } finally {
                submit.querySelector('.np-btn-label').classList.remove('d-none');
                submit.querySelector('.spinner-border').classList.add('d-none');
                submit.disabled = false;
            }
        });
    }
});

// ───────── จัดการ Location (add / edit / delete) ในหน้า Pool ─────────
// เติมข้อมูลใน edit modal จาก data attribute ของปุ่ม
document.getElementById('editModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn) return;   // เปิดเองตอน validation error → ใช้ค่าที่ server เติมไว้
    document.getElementById('editForm').action   = '/admin/locations/' + btn.dataset.id + '/update';
    document.getElementById('editName').value    = btn.dataset.name;
    document.getElementById('editNameEn').value  = btn.dataset.nameEn;
    document.getElementById('editSsid').value    = btn.dataset.ssid;
});

// ปุ่ม Save ของ edit: ปิดไว้จนกว่าจะมีการแก้ค่าใดค่าหนึ่ง
(function () {
    const form    = document.getElementById('editForm');
    const saveBtn = document.getElementById('editSaveBtn');
    const fields  = ['editNameEn', 'editName', 'editSsid'];
    let snapshot = null;
    function vals() {
        return JSON.stringify(fields.map(function (id) {
            return document.getElementById(id).value.trim();
        }));
    }
    function update() { saveBtn.disabled = snapshot === null || vals() === snapshot; }
    document.getElementById('editModal').addEventListener('shown.bs.modal', function () {
        snapshot = vals();
        update();
    });
    form.addEventListener('input', update);
})();

// เติม action URL และชื่อพื้นที่ใน delete modal
document.getElementById('deleteModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn) return;
    document.getElementById('deleteForm').action = '/admin/locations/' + btn.dataset.id + '/delete';
    document.getElementById('deleteLocName').textContent = btn.dataset.name ? '"' + btn.dataset.name + '"' : '';
});

// เปิด modal เดิมกลับมาเมื่อ validation ฝั่ง server ไม่ผ่าน
window.addEventListener('load', function () {
<?php if ($isAddErr): ?>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('addModal')).show();
<?php elseif ($isEditErr): ?>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
<?php endif ?>
});

// เริ่มพิมพ์ในช่อง → ลบกรอบแดง + ข้อความ error ทันที
document.querySelectorAll('.np-modal .form-control').forEach(function (inp) {
    inp.addEventListener('input', function () {
        inp.classList.remove('np-invalid');
        var msg = inp.nextElementSibling;
        if (msg && msg.classList.contains('np-field-err')) { msg.remove(); }
    });
});

// ตรวจช่องบังคับ (data-req) ฝั่ง client — เตือน inline ไม่ปิด/รีโหลด dialog
function npValidateForm(form) {
    var ok = true;
    form.querySelectorAll('[data-req]').forEach(function (inp) {
        inp.classList.remove('np-invalid');
        var sib = inp.nextElementSibling;
        if (sib && sib.classList.contains('np-field-err')) { sib.remove(); }
        if (inp.value.trim() === '') {
            ok = false;
            inp.classList.add('np-invalid');
            var p = document.createElement('p');
            p.className = 'np-field-err';
            p.innerHTML = '<i class="bi bi-info-circle-fill"></i> ' + inp.dataset.reqmsg;
            inp.insertAdjacentElement('afterend', p);
        }
    });
    if (! ok) { var first = form.querySelector('.np-invalid'); if (first) { first.focus(); } }
    return ok;
}
document.querySelectorAll('#addModal form, #editForm').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        if (! npValidateForm(form)) { e.preventDefault(); }
    });
});
</script>

<!-- Add Location: ปิดปุ่ม Save จนกว่าจะกรอกช่องบังคับครบ + validate ตอน submit (สคริปต์แยกอิสระ กัน error จากก้อนอื่น) -->
<script>
(function () {
    var addForm = document.querySelector('#addModal form');
    if (! addForm) { return; }
    var saveBtn = addForm.querySelector('button[type="submit"]');
    var reqs    = addForm.querySelectorAll('[data-req]');

    // เปิดปุ่มเฉพาะเมื่อช่องบังคับ (Name EN + SSID) ถูกกรอกครบ
    function syncBtn() {
        if (! saveBtn) { return; }
        var filled = true;
        reqs.forEach(function (i) { if (i.value.trim() === '') { filled = false; } });
        saveBtn.disabled = ! filled;
    }
    addForm.addEventListener('input', syncBtn);
    // sync ตอนเปิด modal ด้วย (เผื่อ server เติมค่ากลับมาหลัง validation fail → เปิดปุ่มให้กดซ้ำได้)
    var addModal = document.getElementById('addModal');
    if (addModal) { addModal.addEventListener('shown.bs.modal', syncBtn); }
    syncBtn();   // เริ่มต้น: ปิดปุ่มจนกว่าจะกรอกครบ
})();
</script>
<?= $this->endSection() ?>
