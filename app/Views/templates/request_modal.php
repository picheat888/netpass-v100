<?php

/**
 * Request Modal — Wizard ขอ Voucher Wi-Fi (5 ขั้น) + พิมพ์ตั๋ว 3 ใบ/แถว
 * รวม data จาก model โดยตรง เพราะต้องแสดงในทุกหน้า (sidebar)
 */
helper('netpass');

$_locModel  = model('App\Models\LocationModel');
$_vouModel  = model('App\Models\VoucherModel');

$_locations = $_locModel->findAll();

// นับ stock แยก location_id × duration
$_stockRows = $_vouModel
    ->select('location_id, duration, COUNT(*) AS cnt')
    ->where('status', 'instock')
    ->groupBy(['location_id', 'duration'])
    ->get()->getResultArray();

$_stock    = [];   // $_stock[loc_id][duration] = count
$_locStock = [];   // $_locStock[loc_id] = total instock (ทุก duration รวมกัน)
foreach ($_stockRows as $_row) {
    $_stock[$_row['location_id']][$_row['duration']] = (int) $_row['cnt'];
    $_locStock[$_row['location_id']] = ($_locStock[$_row['location_id']] ?? 0) + (int) $_row['cnt'];
}

$_durations = voucher_durations();
$_isEn      = service('request')->getLocale() === 'en';
$_locName   = static fn ($loc) => $_isEn ? (($loc['name_en'] ?? '') ?: $loc['name']) : $loc['name'];
?>
<!-- CSS ของ Request Modal แยกไว้ที่ assets/css/request-modal.css -->
<link rel="stylesheet" href="<?= base_url('assets/css/request-modal.css') ?>">

<!-- ════════════════════ REQUEST MODAL ════════════════════ -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <!-- แถบหัวเต็มความกว้าง: ชื่อ dialog (ซ้าย) + ปุ่มปิด (ขวา) -->
            <div class="np-rq-header">
                <div class="np-rq-brand"><span class="ico"><i class="bi bi-wifi"></i></span><?= lang('Voucher.requestTitle') ?></div>
                <button type="button" class="np-rq-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="np-rq">
                <!-- แถบขั้นตอนแนวตั้ง (rail) -->
                <aside class="np-rq-rail">
                    <div class="np-steps">
                        <div class="np-step active" data-step="1"><div class="np-step-num">1</div><div class="np-step-lbl"><span class="k"><?= lang('Voucher.stepLabel') ?> 1</span><span class="t"><?= lang('Voucher.stepTerms') ?></span></div></div>
                        <div class="np-step" data-step="2"><div class="np-step-num">2</div><div class="np-step-lbl"><span class="k"><?= lang('Voucher.stepLabel') ?> 2</span><span class="t"><?= lang('Voucher.stepLocation') ?></span></div></div>
                        <div class="np-step" data-step="3"><div class="np-step-num">3</div><div class="np-step-lbl"><span class="k"><?= lang('Voucher.stepLabel') ?> 3</span><span class="t"><?= lang('Voucher.stepDuration') ?></span></div></div>
                        <div class="np-step" data-step="4"><div class="np-step-num">4</div><div class="np-step-lbl"><span class="k"><?= lang('Voucher.stepLabel') ?> 4</span><span class="t"><?= lang('Voucher.stepGuests') ?></span></div></div>
                        <div class="np-step" data-step="5"><div class="np-step-num">5</div><div class="np-step-lbl"><span class="k"><?= lang('Voucher.stepLabel') ?> 5</span><span class="t"><?= lang('Voucher.stepConfirm') ?></span></div></div>
                    </div>
                </aside>

                <!-- คอลัมน์เนื้อหา -->
                <div class="np-rq-main">
                    <div class="np-rq-top">
                        <div>
                            <h5 class="modal-title mb-0 fw-bold" id="reqStepTitle"><?= lang('Voucher.termsTitle') ?></h5>
                            <p class="text-muted mb-0" id="reqStepSub"><?= lang('Voucher.termsSub') ?></p>
                        </div>
                        <?php /* ฝั่งขวาของหัว: ปุ่ม Add — โชว์เฉพาะ Step 4 Guest details */ ?>
                        <div id="reqStepAction" class="np-rq-top-action d-none">
                            <button type="button" id="reqAddGuest" class="btn btn-np"><i class="bi bi-plus-lg me-1"></i><?= lang('Voucher.addGuest') ?></button>
                        </div>
                        <?php /* แจ้งเตือนจำนวน guest เกิน max — ยืดเต็มความกว้าง ตกลงบรรทัดใหม่ใต้หัว step */ ?>
                        <p class="np-valid-alert mb-0 d-none" id="reqGuestErr"></p>
                    </div>

                    <div class="np-rq-body">
                        <!-- ── Step 1: Terms ── -->
                        <div id="reqStep1" class="np-step-pane">
                            <div class="np-terms-box">
                                <?php /* แต่ละหัวข้อ + เนื้อหา: แตกเนื้อหาเป็นย่อหน้าละ <p> เพื่อคุมระยะห่างด้วย margin */ ?>
                                <?php for ($_i = 1; $_i <= 5; $_i++): ?>
                                    <div class="np-terms-text"><?= esc(lang("Voucher.termsBtn{$_i}")) ?></div>
                                    <div class="np-terms-content">
                                        <?php foreach (preg_split('/\r\n|\r|\n/', lang("Voucher.termsBody{$_i}")) as $_line): ?>
                                            <?php $_line = ltrim($_line, " \t"); ?>
                                            <?php if ($_line === '') continue; ?>
                                            <p><?= esc($_line) ?></p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="reqAcceptTerms">
                                <label class="form-check-label" for="reqAcceptTerms"><?= lang('Voucher.termsAccept') ?></label>
                            </div>
                        </div>

                        <!-- ── Step 2: Location ── -->
                        <div id="reqStep2" class="np-step-pane d-none">
                            <?php if (empty($_locations)): ?>
                                <p class="text-center text-muted py-4"><?= lang('Voucher.noStock') ?></p>
                            <?php else: ?>
                                <?php foreach ($_locations as $_loc):
                                    $_total = $_locStock[$_loc['id']] ?? 0;
                                ?>
                                <div class="np-loc-card <?= $_total === 0 ? 'np-disabled' : '' ?>"
                                     data-loc-id="<?= $_loc['id'] ?>"
                                     data-loc-name="<?= esc($_locName($_loc)) ?>"
                                     data-loc-ssid="<?= esc($_loc['ssid']) ?>">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="np-loc-icon"><i class="bi bi-wifi"></i></div>
                                        <div class="flex-grow-1">
                                            <div class="np-loc-name"><?= esc($_locName($_loc)) ?></div>
                                            <div class="np-loc-ssid"><i class="bi bi-router me-1"></i><?= esc($_loc['ssid']) ?></div>
                                        </div>
                                        <div class="np-loc-badge <?= $_total === 0 ? 'empty' : '' ?>">
                                            <?= $_total ?>
                                            <div style="font-size:10px;font-weight:400"><?= lang('Voucher.vouchersLeft') ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- ── Step 3: Duration ── -->
                        <div id="reqStep3" class="np-step-pane d-none">
                            <div class="row g-3">
                                <?php foreach ($_durations as $_key => $_d): ?>
                                <div class="col-6 col-md-3">
                                    <div class="np-dur-card" data-dur="<?= $_key ?>">
                                        <div class="np-dur-icon"><i class="bi bi-clock"></i></div>
                                        <div class="np-dur-label"><?= esc($_isEn ? $_d['label_en'] : $_d['label']) ?></div>
                                        <div class="np-dur-sub"><?= esc($_d['sub']) ?></div>
                                        <div class="np-dur-stock">
                                            <span class="np-dur-cnt" data-dur-key="<?= $_key ?>">—</span>
                                            <span style="font-weight:400;font-size:11px"><?= lang('Voucher.vouchersLeft') ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- ── Step 4: Guests — supplier 1 ค่าต่อ request + รายชื่อ guest (แจ้งเตือนจำนวนเกิน max แสดงใต้ sub-title ของหัว step) ── -->
                        <div id="reqStep4" class="np-step-pane d-none">
                            <!-- Supplier เดียวใช้กับ guest ทุกคนในครั้งนี้ -->
                            <div class="np-guest-supplier">
                                <label class="form-label" for="reqSupplier"><?= lang('Voucher.guestSupplier') ?> <span class="np-req">*</span></label>
                                <input type="text" id="reqSupplier" class="form-control form-control-sm" placeholder="<?= esc(lang('Voucher.guestSupplier'), 'attr') ?>">
                                <div class="np-field-err"></div>
                            </div>
                            <div id="reqGuestList"></div>
                        </div>

                        <!-- ── Step 5: Confirm ── -->
                        <div id="reqStep5" class="np-step-pane d-none">
                            <div class="np-confirm-box">
                                <div class="np-confirm-row">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <div>
                                        <div class="np-confirm-lbl"><?= lang('Voucher.area') ?></div>
                                        <div class="np-confirm-val" id="confirmLoc">—</div>
                                    </div>
                                </div>
                                <div class="np-confirm-row">
                                    <i class="bi bi-clock-fill"></i>
                                    <div>
                                        <div class="np-confirm-lbl"><?= lang('Voucher.duration') ?></div>
                                        <div class="np-confirm-val" id="confirmDur">—</div>
                                    </div>
                                </div>
                                <div class="np-confirm-row">
                                    <i class="bi bi-people-fill"></i>
                                    <div>
                                        <div class="np-confirm-lbl"><?= lang('Voucher.confirmCount') ?></div>
                                        <div class="np-confirm-val" id="confirmCount">—</div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted mt-3 mb-0" style="font-size:13px">
                                <i class="bi bi-info-circle me-1"></i><?= lang('Voucher.step3Sub') ?>
                            </p>
                        </div>

                        <!-- ── Step 6: Result (แสดงก่อนสั่งพิมพ์) ── -->
                        <div id="reqStep6" class="np-step-pane d-none">
                            <div class="text-center py-2">
                                <div style="width:56px;height:56px;border-radius:50%;background:#e6f6ef;color:#0EA66B;display:inline-flex;align-items:center;justify-content:center;font-size:28px"><i class="bi bi-check-lg"></i></div>
                                <h5 class="fw-bold mt-2 mb-1"><?= lang('Voucher.issuedTitle') ?></h5>
                                <p class="text-muted mb-3" id="reqResultCount" style="font-size:13px">—</p>
                            </div>
                            <div id="reqResultList"></div>
                        </div>
                    </div>

                    <div class="np-rq-foot">
                        <button id="reqBack" type="button" class="btn btn-outline-secondary d-none">
                            <i class="bi bi-chevron-left me-1"></i><?= lang('Voucher.btnBack') ?>
                        </button>
                        <button id="reqNext" type="button" class="btn btn-np ms-auto" disabled>
                            <?= lang('Voucher.btnNext') ?><i class="bi bi-chevron-right ms-1"></i>
                        </button>
                        <button id="reqConfirm" type="button" class="btn btn-np ms-auto d-none">
                            <i class="bi bi-check-circle me-1"></i><?= lang('Voucher.btnConfirm') ?>
                        </button>
                        <button id="reqPrintAll" type="button" class="btn btn-np ms-auto d-none">
                            <i class="bi bi-printer me-1"></i><?= lang('Voucher.btnPrint') ?>
                        </button>
                        <button id="reqDone" type="button" class="btn btn-outline-secondary d-none" data-bs-dismiss="modal">
                            <?= lang('Voucher.btnClose') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data สำหรับ JS (stock, csrf, lang) -->
<script>
const NP_STOCK   = <?= json_encode($_stock) ?>;
const NP_CSRF    = {token:'<?= csrf_token() ?>',hash:'<?= csrf_hash() ?>'};
const NP_REQ_URL = '<?= site_url('voucher/request') ?>';
const NP_CSS = {
    main:  '<?= base_url('assets/css/style.css') ?>',
    icons: '<?= base_url('assets/plugins/bootstrap-icons/bootstrap-icons.min.css') ?>',
    fonts: '<?= base_url('assets/fonts/fonts.css') ?>'
};
const NP_L = {
    no:    <?= json_encode(lang('Voucher.guestNo')) ?>,
    sup:   <?= json_encode(lang('Voucher.guestSupplier')) ?>,
    first: <?= json_encode(lang('Voucher.guestFirst')) ?>,
    last:  <?= json_encode(lang('Voucher.guestLast')) ?>,
    phone: <?= json_encode(lang('Voucher.guestPhone')) ?>,
    incomplete: <?= json_encode(lang('Voucher.errGuestIncomplete')) ?>,
    maxStock:   <?= json_encode(lang('Voucher.errMaxStock')) ?>,
    maxGuest:   <?= json_encode(lang('Voucher.errMaxGuest')) ?>,
    required:   <?= json_encode(lang('Voucher.errRequired')) ?>,
    phoneInvalid: <?= json_encode(lang('Voucher.errPhoneInvalid')) ?>,
    dupPhone:   <?= json_encode(lang('Voucher.errDupPhone')) ?>,
    dupName:    <?= json_encode(lang('Voucher.errDupName')) ?>,
    countUnit:  <?= json_encode(lang('Voucher.confirmCountUnit')) ?>,
    cardTitle:  <?= json_encode(lang('Voucher.cardTitle')) ?>,
    lblWifi:    <?= json_encode(lang('Voucher.lblWifiName')) ?>,
    colLoc:     <?= json_encode(lang('Voucher.colLocation')) ?>,
    colDur:     <?= json_encode(lang('Voucher.colDuration')) ?>,
    colExp:     <?= json_encode(lang('Voucher.colExpiresAt')) ?>,
    scan:       <?= json_encode(lang('Voucher.scanConnect')) ?>,
    genErr:     <?= json_encode(lang('Voucher.errGeneral')) ?>,
    btnConfirm: <?= json_encode(lang('Voucher.btnConfirm')) ?>,
    area:       <?= json_encode(lang('Voucher.area')) ?>,
    saveImage:  <?= json_encode(lang('Voucher.saveImage')) ?>
};
const NP_STEP_TITLES = [
    '',
    {title:<?= json_encode(lang('Voucher.termsTitle')) ?>, sub:<?= json_encode(lang('Voucher.termsSub')) ?>},
    {title:<?= json_encode(lang('Voucher.step1Title')) ?>, sub:<?= json_encode(lang('Voucher.step1Sub')) ?>},
    {title:<?= json_encode(lang('Voucher.step2Title')) ?>, sub:<?= json_encode(lang('Voucher.step2Sub')) ?>},
    {title:<?= json_encode(lang('Voucher.guestsTitle')) ?>, sub:<?= json_encode(lang('Voucher.guestsSub')) ?>},
    {title:<?= json_encode(lang('Voucher.step3Title')) ?>, sub:<?= json_encode(lang('Voucher.step3Sub')) ?>}
];
</script>
<script src="<?= base_url('assets/plugins/qrcode/qrcode-generator.min.js') ?>"></script>
<script><?= file_get_contents(__DIR__ . '/request_modal.js') ?></script>
