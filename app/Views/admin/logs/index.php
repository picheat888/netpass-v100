<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="np-card np-dt">
    <!-- ตัวกรอง: ประเภทการกระทำ + ช่วงวันที่ (DataTables วางไว้ใน toolbar ซ้าย ถัดจากค้นหา) -->
    <div id="lgToolbar" class="d-flex flex-wrap align-items-center gap-2">
        <select id="lgAction" class="form-select" style="width:auto">
            <option value=""><?= lang('Activity.filterAllActions') ?></option>
            <?php foreach ($actions as $key => $label): ?>
                <option value="<?= esc($key, 'attr') ?>"><?= esc($label) ?></option>
            <?php endforeach ?>
        </select>
        <input type="date" id="lgFrom" class="form-control" style="width:auto" aria-label="<?= esc(lang('Activity.dateFrom'), 'attr') ?>" title="<?= esc(lang('Activity.dateFrom'), 'attr') ?>">
        <span class="text-muted">–</span>
        <input type="date" id="lgTo" class="form-control" style="width:auto" aria-label="<?= esc(lang('Activity.dateTo'), 'attr') ?>" title="<?= esc(lang('Activity.dateTo'), 'attr') ?>">
        <!-- เตือนช่วงวันที่ก่อน export — อยู่ข้างช่อง To -->
        <span id="lgExportErr" class="np-field-err np-export-err d-none"><i class="bi bi-info-circle-fill"></i> <?= lang('Activity.exportNeedPeriod') ?></span>
    </div>
    <!-- ปุ่มส่งออก (DataTables วางไว้ใน toolbar ขวา) -->
    <div id="lgActionBar" class="d-flex gap-2">
        <button type="button" id="lgExport" class="btn btn-np-outline">
            <i class="bi bi-filetype-csv me-1"></i><?= lang('Activity.export') ?>
        </button>
    </div>

    <table id="logTable" class="np-table align-middle" style="width:100%">
        <thead>
            <tr>
                <th><?= lang('Activity.colTime') ?></th>
                <th><?= lang('Activity.colActor') ?></th>
                <th><?= lang('Activity.colRole') ?></th>
                <th><?= lang('Activity.colUsername') ?></th>
                <th><?= lang('Activity.colAction') ?></th>
                <th><?= lang('Activity.colType') ?></th>
                <th><?= lang('Activity.colTarget') ?></th>
                <th><?= lang('Activity.colDetail') ?></th>
                <th><?= lang('Activity.colIp') ?></th>
                <th class="text-end"><?= lang('Activity.view') ?></th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>

<?= $this->section('modals') ?>
<!-- Dialog: รายละเอียด event (เติม body ด้วย JS) -->
<div class="modal fade np-dialog-modal" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content np-dialog">
            <div class="dlg-head">
                <span class="dlg-ico is-confirm"><i class="bi bi-card-list"></i></span>
                <div class="dlg-htext">
                    <h5 id="logDetailAction"><?= lang('Activity.detailTitle') ?></h5>
                    <p id="logDetailMeta"><?= lang('Activity.detailSub') ?></p>
                </div>
                <button type="button" class="dlg-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="dlg-body" id="logDetailBody"></div>
            <div class="dlg-foot">
                <button type="button" class="dlg-btn dlg-btn-light" data-bs-dismiss="modal"><?= lang('Common.cancel') ?></button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// ───────── ค่าจาก server (PHP อยู่ที่นี่ที่เดียว) ─────────
window.NP_LOGS = {
    urls: { data: '<?= site_url('admin/logs/data') ?>', export: '<?= site_url('admin/logs/export') ?>' },
    i18n: {
        noDetails: <?= json_encode(lang('Activity.noDetails')) ?>,
        field:     <?= json_encode(lang('Common.changeField')) ?>,
        before:    <?= json_encode(lang('Common.changeOld')) ?>,
        after:     <?= json_encode(lang('Common.changeNew')) ?>,
        value:     <?= json_encode(lang('Common.changeValue')) ?>,
        guestList: <?= json_encode(lang('Activity.guestList')) ?>,
        gName:     <?= json_encode(lang('Activity.colName')) ?>,
        gPhone:    <?= json_encode(lang('Activity.colPhone')) ?>,
        gUser:     <?= json_encode(lang('Activity.colUsername')) ?>
    }
};
</script>
<script><?= file_get_contents(__DIR__ . '/index.js') ?></script>
<?= $this->endSection() ?>
