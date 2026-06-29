<?= $this->extend('layouts/user') ?>

<?= $this->section('content') ?>
<?php
$isEn    = service('request')->getLocale() === 'en';
$locName = static fn ($loc) => $isEn ? (($loc['name_en'] ?? '') ?: $loc['name']) : $loc['name'];
?>

<div class="np-card np-dt np-dt-cards">
    <!-- ตัวกรอง (DataTables วางไว้ใน toolbar ซ้าย ถัดจากช่องค้นหา) -->
    <div id="vfToolbar" class="d-flex flex-wrap align-items-center gap-2">
        <select id="vfLoc" class="form-select vf-loc" style="width:auto">
            <option value=""><?= lang('Voucher.filterAll') ?></option>
            <?php foreach ($locations as $location): ?>
                <option value="<?= $location['id'] ?>"><?= esc($locName($location)) ?></option>
            <?php endforeach ?>
        </select>
        <select id="vfStatus" class="form-select vf-status" style="width:auto">
            <option value=""><?= lang('Voucher.filterAll') ?></option>
            <option value="active"><?= lang('Voucher.filterActive') ?></option>
            <option value="expired"><?= lang('Voucher.filterExpired') ?></option>
        </select>
    </div>
    <!-- ปุ่มพิมพ์ตั๋วที่เลือก + ขอ voucher (DataTables วางไว้ใน toolbar ขวา) -->
    <div id="vfAction" class="d-flex gap-2">
        <button type="button" id="vchPrintSel" class="btn btn-np-outline d-none">
            <i class="bi bi-printer me-1"></i><?= lang('Voucher.btnPrint') ?> (<span id="vchPrintCount">0</span>)
        </button>
        <button type="button" class="btn btn-np" data-bs-toggle="modal" data-bs-target="#requestModal">
            <i class="bi bi-plus-lg me-1"></i><?= lang('Nav.request') ?>
        </button>
    </div>

    <table id="voucherTable" class="np-table align-middle" style="width:100%">
        <thead>
            <tr>
                <th style="width:36px"><input type="checkbox" class="form-check-input" id="vchSelectAll" aria-label="select all"></th>
                <th><?= lang('Voucher.colSupplier') ?></th>
                <th><?= lang('Voucher.colGuest') ?></th>
                <th><?= lang('Voucher.colLocation') ?></th>
                <th><?= lang('Voucher.colDuration') ?></th>
                <th><?= lang('Voucher.colIssuedAt') ?></th>
                <th><?= lang('Voucher.colExpiresAt') ?></th>
                <th><?= lang('Voucher.colStatus') ?></th>
                <th class="text-end"><?= lang('Voucher.colActions') ?></th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>

<?= $this->section('modals') ?>
<!-- Modal: รายละเอียด Voucher — การ์ดตั๋ว เติมข้อมูลจาก data-attribute ของปุ่ม -->
<div class="modal fade" id="voucherModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered vm-modal">
        <div class="modal-content vm-content">
            <!-- หัว: pill สถานะ + ปุ่มปิด -->
            <div class="vm-head">
                <span class="vm-status-pill" id="vmStatus">
                    <i class="bi bi-check-lg"></i><span id="vmStatusText"></span>
                </span>
                <div class="vm-head-btns">
                    <button type="button" class="vm-close" id="vmSaveImg" title="<?= esc(lang('Voucher.saveImage'), 'attr') ?>">
                        <i class="bi bi-floppy"></i>
                    </button>
                    <button type="button" class="vm-close" data-bs-dismiss="modal" aria-label="<?= esc(lang('Voucher.btnClose'), 'attr') ?>">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- ตั๋ว -->
            <div class="vm-ticket-wrap">
                <div class="vm-ticket">
                    <!-- ฝั่งซ้าย: ข้อมูล voucher -->
                    <div class="vm-ticket-main">
                        <div class="vm-ticket-title">
                            <i class="bi bi-wifi"></i><span><?= lang('Voucher.cardTitle') ?></span>
                        </div>

                        <div class="vm-meta">
                            <div class="vm-meta-col">
                                <div class="vm-meta-label"><?= lang('Voucher.lblWifiName') ?></div>
                                <div class="vm-meta-val font-mono" id="vmSsid"></div>
                            </div>
                            <div class="vm-meta-col">
                                <div class="vm-meta-label"><?= lang('Voucher.colLocation') ?></div>
                                <div class="vm-meta-val d-flex align-items-center gap-2">
                                    <span class="np-dot" id="vmDot"></span>
                                    <span class="text-truncate" id="vmLoc"></span>
                                </div>
                            </div>
                        </div>

                        <!-- กล่อง credential + ปุ่ม copy -->
                        <div class="vm-creds">
                            <div class="vm-cred">
                                <div class="vm-cred-top">
                                    <span class="vm-cred-label">USERNAME</span>
                                    <button type="button" class="vm-copy" data-copy="vmUser" title="<?= esc(lang('Voucher.copyUser'), 'attr') ?>">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                </div>
                                <div class="vm-cred-val font-mono" id="vmUser"></div>
                            </div>
                            <div class="vm-cred">
                                <div class="vm-cred-top">
                                    <span class="vm-cred-label">PASSWORD</span>
                                    <button type="button" class="vm-copy" data-copy="vmPass" title="<?= esc(lang('Voucher.copyPass'), 'attr') ?>">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                </div>
                                <div class="vm-cred-val font-mono" id="vmPass"></div>
                            </div>
                        </div>

                        <div class="vm-foot">
                            <span><?= lang('Voucher.colDuration') ?> <b id="vmDur"></b></span>
                            <span><?= lang('Voucher.colExpiresAt') ?> <b id="vmExpires"></b></span>
                        </div>
                    </div>

                    <!-- ฝั่งขวา: QR (มีรอยปรุคั่น) — หัวเป็นชื่อ Wi-Fi -->
                    <div class="vm-ticket-qr">
                        <div class="vm-qr-name font-mono" id="vmQrName"></div>
                        <div class="vm-qr" id="vmQr"></div>
                        <div class="vm-qr-cap"><?= lang('Voucher.scanConnect') ?></div>
                    </div>
                </div>
            </div>
                <div class="vm-info">
                    <div class="vm-info-title"><i class="bi bi-person-lines-fill me-1"></i><?= lang('Voucher.reqInfoTitle') ?></div>
                    <div class="vm-info-grid">
                        <div class="vm-info-row"><span class="vm-info-k"><?= lang('Voucher.guestSupplier') ?></span><span class="vm-info-v" id="vmSupplier">—</span></div>
                        <div class="vm-info-row"><span class="vm-info-k"><?= lang('Voucher.guestFullName') ?></span><span class="vm-info-v" id="vmGuestFull">—</span></div>
                        <div class="vm-info-row"><span class="vm-info-k"><?= lang('Voucher.guestPhone') ?></span><span class="vm-info-v" id="vmPhone">—</span></div>
                        <div class="vm-info-row"><span class="vm-info-k"><?= lang('Voucher.colIssuedAt') ?></span><span class="vm-info-v" id="vmIssued">—</span></div>
                    </div>
                </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- QR generator (สร้าง QR ฝั่ง client — ข้อมูลไม่ออกนอกเบราว์เซอร์) -->
<script src="<?= base_url('assets/plugins/qrcode/qrcode-generator.min.js') ?>"></script>
<script>
// ───────── ค่าจาก server (PHP อยู่ที่นี่ที่เดียว) ─────────
window.NP_MYVOUCHER = {
    urls: {
        data:    '<?= site_url('myvoucher/data') ?>',
        tickets: '<?= site_url('myvoucher/tickets') ?>'
    },
    assets: {
        cssMain:  '<?= base_url('assets/css/style.css') ?>',
        cssIcons: '<?= base_url('assets/plugins/bootstrap-icons/bootstrap-icons.min.css') ?>',
        cssFonts: '<?= base_url('assets/fonts/fonts.css') ?>'
    },
    i18n: {
        copied: <?= json_encode(lang('Voucher.copied')) ?>,
        title:  <?= json_encode(lang('Voucher.cardTitle')) ?>,
        wifi:   <?= json_encode(lang('Voucher.lblWifiName')) ?>,
        loc:    <?= json_encode(lang('Voucher.colLocation')) ?>,
        dur:    <?= json_encode(lang('Voucher.colDuration')) ?>,
        exp:    <?= json_encode(lang('Voucher.colExpiresAt')) ?>,
        scan:   <?= json_encode(lang('Voucher.scanConnect')) ?>,
        genErr: <?= json_encode(lang('Voucher.errGeneral')) ?>
    }
};
</script>
<script><?= file_get_contents(__DIR__ . '/index.js') ?></script>
<?= $this->endSection() ?>
