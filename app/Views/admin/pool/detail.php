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
                <button type="submit" class="btn btn-np"><?= lang('Common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ยืนยันลบ voucher (branded — โทนแดง กว้าง 430) -->
<div class="modal fade np-modal np-modal-confirm" id="vDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="vDeleteForm" action="">
            <?= csrf_field() ?>
            <div class="modal-header">
                <span class="np-modal-ico is-danger"><i class="bi bi-trash3-fill"></i></span>
                <div class="np-modal-htext">
                    <h5><?= lang('Pool.deleteTitle') ?></h5>
                    <p><?= lang('Pool.deleteSub') ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0"><?= lang('Pool.confirmDelete') ?></p>
                <p class="fw-semibold mt-1 mb-0 font-mono" id="vDeleteUser"></p>
                <div class="np-callout is-danger">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <span><?= lang('Pool.deleteImpact') ?></span>
                </div>
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
// edit modal — เติมค่าจากปุ่มที่กด (รองรับแถวที่ DataTables สร้างใหม่)
document.getElementById('editModal').addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget; if (!b) return;
    document.getElementById('editForm').action = '<?= site_url('admin/pool/voucher') ?>/' + b.dataset.id + '/update';
    document.getElementById('editUser').value = b.dataset.user;
    document.getElementById('editPass').value = b.dataset.pass;
});

// delete voucher modal — เติม action + username จากปุ่มที่กด
document.getElementById('vDeleteModal').addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget; if (!b) return;
    document.getElementById('vDeleteForm').action = '<?= site_url('admin/pool/voucher') ?>/' + b.dataset.id + '/delete';
    document.getElementById('vDeleteUser').textContent = b.dataset.user || '';
});

// ตาราง DataTables server-side
document.addEventListener('DOMContentLoaded', function () {
    const stSel = document.getElementById('stStatus');
    const dt = NetPass.dataTable('#poolDetailTable', {
        filters: '#stToolbar',
        ajax: {
            url: '<?= site_url('admin/pool/location/' . $location['id'] . '/data') ?>',
            data: function (d) { d.status = stSel.value; }
        },
        order: [],
        columns: [
            { orderable: false },                       // ลำดับ (#)
            { orderable: true },                        // username
            { orderable: false },                       // password
            { orderable: true },                        // ระยะเวลา
            { orderable: true },                        // สถานะ
            { orderable: true },                        // วันที่นำเข้า
            { orderable: false, className: 'text-end' } // จัดการ
        ]
    });
    stSel.addEventListener('change', function () { dt.ajax.reload(); });
});
</script>
<?= $this->endSection() ?>
