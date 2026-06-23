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

                        <!-- ── Step 4: Guests — validation รวม (max/stock) อยู่เหนือ card, รายชื่อ guest อยู่ในลิสต์ ── -->
                        <div id="reqStep4" class="np-step-pane d-none">
                            <p class="np-valid-alert mb-2 d-none" id="reqGuestErr"></p>
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
    countUnit:  <?= json_encode(lang('Voucher.confirmCountUnit')) ?>,
    cardTitle:  <?= json_encode(lang('Voucher.cardTitle')) ?>,
    lblWifi:    <?= json_encode(lang('Voucher.lblWifiName')) ?>,
    colLoc:     <?= json_encode(lang('Voucher.colLocation')) ?>,
    colDur:     <?= json_encode(lang('Voucher.colDuration')) ?>,
    colExp:     <?= json_encode(lang('Voucher.colExpiresAt')) ?>,
    scan:       <?= json_encode(lang('Voucher.scanConnect')) ?>,
    genErr:     <?= json_encode(lang('Voucher.errGeneral')) ?>
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
<script>
(function () {
    'use strict';
    let step = 1, selLocId = null, selLocName = '', selDur = null, selDurLabel = '';

    const reqModal  = document.getElementById('requestModal');
    if (!reqModal) return;
    const btnNext   = document.getElementById('reqNext');
    const btnBack   = document.getElementById('reqBack');
    const btnConf   = document.getElementById('reqConfirm');
    const stepTitle = document.getElementById('reqStepTitle');
    const stepSub   = document.getElementById('reqStepSub');
    const accept    = document.getElementById('reqAcceptTerms');
    const guestList = document.getElementById('reqGuestList');
    const guestErr  = document.getElementById('reqGuestErr');
    const LAST_STEP = 5;
    let issuedTickets = [];

    function esc(s){ const d=document.createElement('div'); d.textContent=s==null?'':s; return d.innerHTML; }
    function wifiEsc(s){ return String(s||'').replace(/([\\;,:"])/g,'\\$1'); }
    function maxStock(){ return (NP_STOCK[selLocId] && NP_STOCK[selLocId][selDur]) || 0; }

    function showStep(n){
        step = n;
        document.querySelectorAll('.np-step-pane').forEach(p=>p.classList.add('d-none'));
        document.getElementById('reqStep'+n).classList.remove('d-none');
        // รีเซ็ต scroll กลับบนสุดทุกครั้งที่เปลี่ยน step (กัน scroll ค้างจาก step ก่อน)
        const body = document.querySelector('#requestModal .np-rq-body');
        if (body) body.scrollTop = 0;
        document.querySelectorAll('.np-step').forEach(s=>{
            const sn=parseInt(s.dataset.step);
            s.classList.toggle('active', sn===n);
            s.classList.toggle('done', sn<n);
        });
        const t=NP_STEP_TITLES[n];
        if(t){ stepTitle.textContent=t.title; stepSub.textContent=t.sub; }
        const isResult = n > LAST_STEP;
        btnBack.classList.toggle('d-none', n===1 || isResult);
        btnNext.classList.toggle('d-none', n>=LAST_STEP);
        btnConf.classList.toggle('d-none', n!==LAST_STEP);
        document.getElementById('reqPrintAll').classList.toggle('d-none', !isResult);
        document.getElementById('reqDone').classList.toggle('d-none', !isResult);
        if(isResult){ stepTitle.textContent=''; stepSub.textContent=''; }
        // โชว์ error + ปุ่ม Add ที่หัว เฉพาะ Step 4 (Guest details)
        document.getElementById('reqStepAction').classList.toggle('d-none', n !== 4);
        updateNextState();
    }

    function updateNextState(){
        if(step===1) btnNext.disabled = !accept.checked;
        else if(step===2) btnNext.disabled = !selLocId;
        else if(step===3) btnNext.disabled = !selDur;
        else if(step===4) btnNext.disabled = guestList.querySelectorAll('.np-guest-row').length===0;
    }

    // Step 1: terms gate
    accept.addEventListener('change', updateNextState);

    // Step 2: location
    document.querySelectorAll('.np-loc-card:not(.np-disabled)').forEach(card=>{
        card.addEventListener('click', function(){
            document.querySelectorAll('.np-loc-card').forEach(c=>c.classList.remove('selected'));
            this.classList.add('selected');
            selLocId=parseInt(this.dataset.locId); selLocName=this.dataset.locName; selDur=null;
            updateNextState();
        });
    });

    // Step 3: duration stock + select
    function refreshDurStock(){
        const locStock = NP_STOCK[selLocId] || {};
        document.querySelectorAll('.np-dur-card').forEach(card=>{
            const dur=card.dataset.dur, cnt=locStock[dur]||0;
            card.querySelector('.np-dur-cnt').textContent=cnt;
            card.classList.toggle('np-disabled', cnt===0);
            if(cnt===0) card.classList.remove('selected');
        });
        selDur=null; selDurLabel='';
        updateNextState();
    }
    document.querySelectorAll('.np-dur-card').forEach(card=>{
        card.addEventListener('click', function(){
            if(this.classList.contains('np-disabled')) return;
            document.querySelectorAll('.np-dur-card').forEach(c=>c.classList.remove('selected'));
            this.classList.add('selected');
            selDur=this.dataset.dur; selDurLabel=this.querySelector('.np-dur-label').textContent;
            updateNextState();
        });
    });

    // Step 4: guest rows
    function guestRowHtml(i){
        // input + ที่ว่างสำหรับข้อความ validation ใต้ช่อง
        function f(cls, ph){
            return '<input type="text" class="form-control form-control-sm '+cls+'" placeholder="'+esc(ph)+'">'
                 + '<div class="np-field-err"></div>';
        }
        return '<div class="np-guest-row">'
            + '<button type="button" class="np-guest-del" title="x"><i class="bi bi-x-lg"></i></button>'
            + '<div class="np-guest-no">'+NP_L.no.replace('{0}', i)+'</div>'
            + '<div class="row g-2">'
            + '<div class="col-12">'+f('g-sup', NP_L.sup)+'</div>'
            + '<div class="col-6">'+f('g-first', NP_L.first)+'</div>'
            + '<div class="col-6">'+f('g-last', NP_L.last)+'</div>'
            + '<div class="col-12">'+f('g-phone', NP_L.phone)+'</div>'
            + '</div></div>';
    }
    function renumber(){
        guestList.querySelectorAll('.np-guest-row').forEach((row,idx)=>{
            row.querySelector('.np-guest-no').textContent = NP_L.no.replace('{0}', idx+1);
        });
    }
    const MAX_GUEST = 12;   // ขอได้สูงสุด 12 รายการต่อครั้ง
    function addGuest(){
        const n = guestList.querySelectorAll('.np-guest-row').length;
        // ครบ 12 รายการต่อครั้ง — เพิ่มไม่ได้
        if(n >= MAX_GUEST){ guestErr.textContent = NP_L.maxGuest.replace('{0}', MAX_GUEST); guestErr.classList.remove('d-none'); return; }
        // เกิน stock คงเหลือ — เพิ่มไม่ได้
        if(n >= maxStock()){ guestErr.textContent = NP_L.maxStock.replace('{0}', maxStock()); guestErr.classList.remove('d-none'); return; }
        guestErr.classList.add('d-none');
        guestList.insertAdjacentHTML('beforeend', guestRowHtml(n+1));
        updateNextState();
    }
    document.getElementById('reqAddGuest').addEventListener('click', addGuest);
    guestList.addEventListener('click', function(e){
        const del=e.target.closest('.np-guest-del');
        if(!del) return;
        if(guestList.querySelectorAll('.np-guest-row').length<=1) return;
        del.closest('.np-guest-row').remove();
        renumber(); guestErr.classList.add('d-none'); updateNextState();
    });
    function collectGuests(){
        const out=[];
        guestList.querySelectorAll('.np-guest-row').forEach(row=>{
            out.push({
                supplier:  row.querySelector('.g-sup').value.trim(),
                firstname: row.querySelector('.g-first').value.trim(),
                lastname:  row.querySelector('.g-last').value.trim(),
                phone:     row.querySelector('.g-phone').value.trim()
            });
        });
        return out;
    }

    // validate รายช่องในการ์ด — ช่องว่างขึ้นขอบแดง + ข้อความใต้ช่อง, แล้ว focus ช่องแรกที่ผิด
    function validateGuests(){
        guestErr.classList.add('d-none');   // ซ่อน alert รวม (ใช้ validation รายช่องในการ์ด)
        let firstBad = null;
        guestList.querySelectorAll('.np-guest-row .form-control').forEach(inp=>{
            const err = inp.nextElementSibling;   // .np-field-err
            if(!inp.value.trim()){
                inp.classList.add('is-invalid');
                if(err){ err.textContent = NP_L.required; err.classList.add('show'); }
                if(!firstBad) firstBad = inp;
            } else {
                inp.classList.remove('is-invalid');
                if(err){ err.textContent = ''; err.classList.remove('show'); }
            }
        });
        if(firstBad){ firstBad.focus(); return false; }
        return true;
    }

    // พิมพ์แล้วล้างสถานะ error ของช่องนั้นทันที
    guestList.addEventListener('input', function(e){
        const inp = e.target;
        if(inp.classList.contains('form-control') && inp.value.trim()){
            inp.classList.remove('is-invalid');
            const err = inp.nextElementSibling;
            if(err){ err.textContent = ''; err.classList.remove('show'); }
        }
    });

    // Next / Back
    btnNext.addEventListener('click', function(){
        if(step===2){ refreshDurStock(); showStep(3); }
        else if(step===3){
            // เข้า step 4: ใส่แถว guest แถวแรกถ้ายังว่าง
            if(guestList.querySelectorAll('.np-guest-row').length===0) addGuest();
            showStep(4);
        }
        else if(step===4){
            // validate รายช่อง — ช่องว่างขึ้นแดง + focus ช่องแรก
            if(!validateGuests()) return;
            const guests=collectGuests();
            document.getElementById('confirmLoc').textContent=selLocName;
            document.getElementById('confirmDur').textContent=selDurLabel;
            document.getElementById('confirmCount').textContent=NP_L.countUnit.replace('{0}', guests.length);
            showStep(5);
        }
        else showStep(step+1);
    });
    btnBack.addEventListener('click', ()=>showStep(step-1));

    // Confirm submit
    btnConf.addEventListener('click', function(){
        const guests=collectGuests();
        btnConf.disabled=true;
        btnConf.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>...';
        const body=new URLSearchParams();
        body.append('location_id', selLocId);
        body.append('duration', selDur);
        body.append(NP_CSRF.token, NP_CSRF.hash);
        guests.forEach((g,i)=>{
            body.append('guests['+i+'][supplier]', g.supplier);
            body.append('guests['+i+'][firstname]', g.firstname);
            body.append('guests['+i+'][lastname]', g.lastname);
            body.append('guests['+i+'][phone]', g.phone);
        });
        fetch(NP_REQ_URL,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body})
        .then(r=>r.json())
        .then(data=>{
            btnConf.disabled=false;
            btnConf.innerHTML='<i class="bi bi-check-circle me-1"></i><?= lang('Voucher.btnConfirm') ?>';
            if(data.success){
                issuedTickets = data.tickets;
                renderResult(data.tickets);
                showStep(6);
                if(window.voucherDT) window.voucherDT.ajax.reload(null,false);
            } else { alert(data.message || NP_L.genErr); }
        })
        .catch(()=>{ btnConf.disabled=false; btnConf.innerHTML='<i class="bi bi-check-circle me-1"></i><?= lang('Voucher.btnConfirm') ?>'; alert(NP_L.genErr); });
    });

    // สร้างการ์ดตั๋ว (vm-ticket) + พิมพ์ 3 ใบ/แถว
    function qrSvg(ssid,pass){ const qr=qrcode(0,'M'); qr.addData('WIFI:T:WPA;S:'+wifiEsc(ssid)+';P:'+wifiEsc(pass)+';;'); qr.make(); return qr.createSvgTag({cellSize:4,margin:0,scalable:true}); }
    function buildTicket(t){
        return '<div class="vm-ticket-wrap"><div class="vm-ticket">'
            + '<div class="vm-ticket-main">'
            +   '<div class="vm-ticket-title"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#3b7ddd" viewBox="0 0 16 16" style="flex-shrink:0"><path d="M15.384 6.115a.485.485 0 0 0-.047-.736A12.444 12.444 0 0 0 8 3C5.259 3 2.723 3.882.663 5.379a.485.485 0 0 0-.048.736.518.518 0 0 0 .668.05A11.448 11.448 0 0 1 8 4c2.507 0 4.827.802 6.716 2.164.205.148.49.13.668-.049z"/><path d="M13.229 8.271c.216-.216.194-.578-.063-.745A9.456 9.456 0 0 0 8 6c-1.905 0-3.68.56-5.166 1.526a.48.48 0 0 0-.063.745.525.525 0 0 0 .652.065A8.46 8.46 0 0 1 8 7c1.689 0 3.24.5 4.576 1.336.206.132.48.108.653-.065zm-2.183 2.183c.226-.226.185-.605-.1-.75A6.473 6.473 0 0 0 8 9c-1.06 0-2.062.254-2.946.704-.285.145-.326.524-.1.75l.015.015c.16.16.408.19.611.09A5.478 5.478 0 0 1 8 10c.764 0 1.49.156 2.15.435.203.1.45.07.611-.09l.085-.092zM9.06 12.44c.196-.196.198-.52-.04-.66A1.99 1.99 0 0 0 8 11.5a1.99 1.99 0 0 0-1.02.28c-.238.14-.236.464-.04.66l.706.706a.5.5 0 0 0 .707 0l.707-.707z"/></svg><span>'+esc(NP_L.cardTitle)+'</span></div>'
            +   '<div class="vm-meta">'
            +     '<div class="vm-meta-col"><div class="vm-meta-label">'+esc(NP_L.lblWifi)+'</div><div class="vm-meta-val font-mono">'+esc(t.ssid)+'</div></div>'
            +     '<div class="vm-meta-col"><div class="vm-meta-label">'+esc(NP_L.colLoc)+'</div><div class="vm-meta-val text-truncate">'+esc(t.location)+'</div></div>'
            +   '</div>'
            +   '<div class="vm-creds">'
            +     '<div class="vm-cred"><div class="vm-cred-top"><span class="vm-cred-label">USERNAME</span></div><div class="vm-cred-val font-mono">'+esc(t.username)+'</div></div>'
            +     '<div class="vm-cred"><div class="vm-cred-top"><span class="vm-cred-label">PASSWORD</span></div><div class="vm-cred-val font-mono">'+esc(t.password)+'</div></div>'
            +   '</div>'
            +   '<div class="vm-foot"><span>'+esc(NP_L.colDur)+' <b>'+esc(t.duration)+'</b></span><span>'+esc(NP_L.colExp)+' <b>'+esc(t.expires_at)+'</b></span></div>'
            + '</div>'
            + '<div class="vm-ticket-qr"><div class="vm-qr-name font-mono">'+esc(t.ssid)+'</div><div class="vm-qr">'+qrSvg(t.ssid,t.password)+'</div><div class="vm-qr-cap">'+esc(NP_L.scan)+'</div></div>'
            + '</div></div>';
    }
    function printTickets(tickets){
        const cards = tickets.map(buildTicket).join('');
        const frame = document.createElement('iframe');
        frame.style.cssText='position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
        frame.onload=function(){ frame.contentWindow.focus(); frame.contentWindow.print(); setTimeout(()=>frame.remove(),1500); };
        frame.srcdoc='<!doctype html><html lang="th"><head><meta charset="utf-8">'
            +'<link rel="stylesheet" href="'+NP_CSS.fonts+'"><link rel="stylesheet" href="'+NP_CSS.icons+'"><link rel="stylesheet" href="'+NP_CSS.main+'">'
            +'<style>*{-webkit-print-color-adjust:exact;print-color-adjust:exact;}body{margin:0;background:#fff;}'
            +'.np-print-sheet{display:flex;flex-wrap:wrap;gap:5mm 4mm;align-content:flex-start;}'
            +'.np-print-sheet .vm-ticket-wrap{padding:0;width:600px;zoom:0.34;break-inside:avoid;}'
            +'.np-dot{display:none;}'
            +'@page{margin:1.27cm;size:A4 portrait;}'
            +'</style></head><body><div class="np-print-sheet">'+cards+'</div></body></html>';
        document.body.appendChild(frame);
    }

    // แสดงรายการที่ออกในหน้าผลลัพธ์ (step 6) + ผูกปุ่มพิมพ์
    function renderResult(tickets){
        document.getElementById('reqResultCount').textContent = NP_L.countUnit.replace('{0}', tickets.length);
        const rows = tickets.map((t, idx) =>
            '<div class="np-confirm-row align-items-start">'
            + '<i class="bi bi-person-badge"></i>'
            + '<div class="flex-grow-1 min-w-0">'
            +   '<div class="np-confirm-val">'+esc(t.guest)+'</div>'
            +   '<div class="np-confirm-lbl mb-1">'
            +     '<span class="d-block"><i class="bi bi-geo-alt me-1"></i><?= esc(lang('Voucher.area')) ?>: '+esc(t.location)+'</span>'
            +     '<span class="d-block"><i class="bi bi-wifi me-1"></i><?= esc(lang('Voucher.lblWifiName')) ?>: '+esc(t.ssid)+'</span>'
            +   '</div>'
            +   '<div class="font-mono" style="font-size:12.5px;color:var(--np-text)">Username: <b>'+esc(t.username)+'</b><br>Password: <b>'+esc(t.password)+'</b></div>'
            + '</div>'
            + '<button type="button" class="btn btn-sm btn-outline-primary np-save-img flex-shrink-0 align-self-start" data-idx="'+idx+'"><i class="bi bi-download me-1"></i><?= esc(lang('Voucher.saveImage')) ?></button>'
            + '</div>'
        ).join('');
        document.getElementById('reqResultList').innerHTML = '<div class="np-confirm-box">'+rows+'</div>';
    }
    document.getElementById('reqPrintAll').addEventListener('click', function(){ if(issuedTickets.length) printTickets(issuedTickets); });

    // บันทึก voucher เป็นรูป PNG ทีละใบ — render การ์ดตั๋วเต็มใบนอกจอแล้ว capture
    async function saveTicketImage(idx, btn){
        const t = issuedTickets[idx];
        if (!t || !window.htmlToImage) return;
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        // กล่องชั่วคราวนอกจอ (vm-ticket ใช้ CSS จาก style.css ของหน้าหลัก)
        const holder = document.createElement('div');
        holder.className = 'np-capture';   // บังคับการ์ดเป็นแนวนอนเหมือนตอนพิมพ์
        holder.style.cssText = 'position:fixed;left:-99999px;top:0;width:730px;background:#fff;';
        holder.innerHTML = buildTicket(t);
        document.body.appendChild(holder);
        try {
            await document.fonts.ready;   // กันฟอนต์ไทยยังโหลดไม่เสร็จ
            const node = holder.querySelector('.vm-ticket-wrap');
            const dataUrl = await htmlToImage.toPng(node, { pixelRatio: 2, backgroundColor: '#ffffff' });
            const a = document.createElement('a');
            a.href = dataUrl;
            a.download = 'voucher-' + (t.username || 'wifi') + '.png';
            a.click();
        } catch (e) {
            alert(NP_L.genErr);
        } finally {
            holder.remove();
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    }

    // ผูกปุ่ม Save แบบ delegation (ปุ่มถูกสร้างแบบ dynamic)
    document.getElementById('reqResultList').addEventListener('click', function(e){
        const btn = e.target.closest('.np-save-img');
        if (btn) saveTicketImage(parseInt(btn.dataset.idx), btn);
    });

    // reset ทุกครั้งที่ปิด/เปิด
    reqModal.addEventListener('hidden.bs.modal', resetWizard);
    function resetWizard(){
        issuedTickets=[];
        selLocId=null; selLocName=''; selDur=null; selDurLabel='';
        accept.checked=false;
        guestList.innerHTML='';
        guestErr.classList.add('d-none');
        document.querySelectorAll('.np-loc-card,.np-dur-card').forEach(c=>c.classList.remove('selected'));
        showStep(1);
    }
    showStep(1);

    // ── เปิด modal อัตโนมัติจาก URL ──
    // เด้ง requestModal ถ้า URL มี #request หรือ ?open=request (reload แล้วยังค้างอยู่)
    function autoOpenFromUrl(){
        const params = new URLSearchParams(location.search);
        const wantOpen = location.hash === '#request' || params.get('open') === 'request';
        if(wantOpen) bootstrap.Modal.getOrCreateInstance(reqModal).show();
    }

    // ใส่ ?open=request ลง URL เมื่อ modal เปิด — reload แล้วยังจับได้ (ครอบทุกปุ่มที่เปิด modal)
    function setOpenInUrl(){
        const url = new URL(location.href);
        url.searchParams.set('open', 'request');
        history.replaceState(null, '', url.pathname + url.search);
    }
    reqModal.addEventListener('shown.bs.modal', setOpenInUrl);

    // ล้าง #request / ?open ออกจาก URL เมื่อปิด modal — reload ครั้งถัดไปจะไม่เด้งซ้ำ
    function clearOpenFromUrl(){
        const url = new URL(location.href);
        url.hash = '';
        url.searchParams.delete('open');
        history.replaceState(null, '', url.pathname + url.search);
    }
    reqModal.addEventListener('hidden.bs.modal', clearOpenFromUrl);

    // รันหลัง DOM + bootstrap โหลดเสร็จ (สคริปต์นี้ถูก include ก่อน bootstrap.bundle)
    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', autoOpenFromUrl);
    } else {
        autoOpenFromUrl();
    }
})();
</script>
