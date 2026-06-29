<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<a href="<?= site_url('admin/pool') ?>" class="btn btn-sm btn-light mb-3 fw-semibold">
    <i class="bi bi-chevron-left me-1"></i><?= lang('Pool.backToPool') ?>
</a>

<div class="np-card np-dt">
    <!-- ตัวกรองสถานะ (DataTables วางไว้ใน toolbar ซ้าย ถัดจากค้นหา) -->
    <div id="stToolbar" class="d-flex flex-wrap align-items-center gap-2">
        <select id="stStatus" class="form-select" style="width:auto">
            <option value=""><?= lang('Common.allStatus') ?></option>
            <option value="instock"><?= lang('Common.instock') ?></option>
            <option value="issued"><?= lang('Common.issued') ?></option>
        </select>
    </div>

    <table id="poolDetailTable" class="np-table align-middle" style="width:100%">
        <thead>
            <tr>
                <th><?= lang('Pool.colNo') ?></th>
                <th><?= lang('Pool.username') ?></th>
                <th><?= lang('Pool.password') ?></th>
                <th><?= lang('Pool.duration') ?></th>
                <th><?= lang('Common.status') ?></th>
                <th><?= lang('Pool.createdAt') ?></th>
                <th class="text-end"><?= lang('Pool.colActions') ?></th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>

<?= $this->section('modals') ?>
<!-- Edit voucher modal (เติมค่าด้วย JS) -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="editForm">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('Pool.editVoucher') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold"><?= lang('Pool.username') ?></label>
                    <input type="text" name="vou_username" id="editUser" class="form-control font-mono" required>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-semibold"><?= lang('Pool.password') ?></label>
                    <input type="text" name="vou_password" id="editPass" class="form-control font-mono" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <!-- disabled ไว้ก่อน เปิดเมื่อมีการแก้ไขจริง (JS) -->
                <button type="submit" class="btn btn-np" id="editSaveBtn" disabled><?= lang('Common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ยืนยันการแก้ไข voucher (confirm) — เด้งทับ edit modal ก่อน submit, โชว์ค่าเดิม → ค่าใหม่ -->
<div class="modal fade np-dialog-modal" id="editConfirmModal" tabindex="-1">
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
                <!-- ตารางเทียบค่าเดิม → ค่าใหม่ (เติมด้วย JS เฉพาะช่องที่เปลี่ยน) -->
                <table class="dlg-cmp-table">
                    <thead>
                        <tr><th><?= lang('Common.changeField') ?></th><th><?= lang('Common.changeOld') ?></th><th><?= lang('Common.changeNew') ?></th></tr>
                    </thead>
                    <tbody id="editDiffBody"></tbody>
                </table>
            </div>
            <div class="dlg-foot">
                <button type="button" class="dlg-btn dlg-btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
                <button type="button" class="dlg-btn dlg-btn-confirm" id="editConfirmBtn"><i class="bi bi-check-lg"></i> <?= lang('Pool.editSaveChanges') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ยืนยันลบ voucher (delete) -->
<div class="modal fade np-dialog-modal" id="vDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content np-dialog" method="post" id="vDeleteForm" action="">
            <?= csrf_field() ?>
            <div class="dlg-head">
                <span class="dlg-ico is-delete"><i class="bi bi-trash3-fill"></i></span>
                <div class="dlg-htext">
                    <h5><?= lang('Pool.deleteTitle') ?></h5>
                    <p><?= lang('Pool.deleteSub') ?></p>
                </div>
                <button type="button" class="dlg-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                <div class="dlg-callout is-delete">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <span><?= lang('Pool.deleteImpact') ?></span>
                </div>
            </div>
            <div class="dlg-body is-centered">
                <div class="dlg-warn-ico is-delete"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <p><?= lang('Pool.confirmDelete') ?></p>
                <p class="dlg-target font-mono" id="vDeleteUser"></p>
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
<script>
// ───────── ค่าจาก server (PHP อยู่ที่นี่ที่เดียว) ─────────
window.NP_POOL_DETAIL = {
    voucherBaseUrl: '<?= site_url('admin/pool/voucher') ?>',
    dataUrl:        '<?= site_url('admin/pool/location/' . $location['id'] . '/data') ?>',
    i18n: {
        username: <?= json_encode(lang('Pool.username')) ?>,
        password: <?= json_encode(lang('Pool.password')) ?>,
    },
};
</script>
<script><?= file_get_contents(__DIR__ . '/detail.js') ?></script>
<?= $this->endSection() ?>
