<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$isEn    = service('request')->getLocale() === 'en';
$locName = static fn ($loc) => $isEn ? (($loc['name_en'] ?? '') ?: $loc['name']) : $loc['name'];
?>

<div class="np-card np-dt">
    <!-- ตัวกรองโดเมน (DataTables วางไว้ใน toolbar ซ้าย ถัดจากช่องค้นหา) -->
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
    <!-- ปุ่มขอ voucher + ปุ่มพิมพ์ตั๋วที่เลือก (DataTables วางไว้ใน toolbar ขวา) -->
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
                <th><?= lang('Voucher.colRequester') ?></th>
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
                <button type="button" class="vm-close" data-bs-dismiss="modal" aria-label="<?= esc(lang('Voucher.btnClose'), 'attr') ?>">
                    <i class="bi bi-x-lg"></i>
                </button>
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
(function () {
    const modal = document.getElementById('voucherModal');
    if (!modal) return;

    const copiedText = <?= json_encode(lang('Voucher.copied')) ?>;

    // escape อักขระพิเศษตามสเปก Wi-Fi QR (\ ; , : ")
    const wifiEsc = (s) => String(s || '').replace(/([\\;,:"])/g, '\\$1');

    // เติมข้อมูล voucher ลงการ์ดตั๋ว จาก data-attribute ของปุ่มที่กด
    modal.addEventListener('show.bs.modal', function (e) {
        const b = e.relatedTarget;
        if (!b) return;
        const d = b.dataset;
        const set = (id, val) => { document.getElementById(id).textContent = val || '—'; };

        set('vmSsid', d.ssid);
        set('vmQrName', d.ssid);
        set('vmLoc', d.loc);
        set('vmUser', d.user);
        set('vmPass', d.pass);
        set('vmDur', d.dur);
        set('vmExpires', d.expires);
        set('vmSupplier', d.supplier);
        set('vmGuestFull', d.guestfull);
        set('vmPhone', d.phone);
        set('vmIssued', d.issued);

        // จุดสีพื้นที่
        document.getElementById('vmDot').style.background = d.color || 'var(--np-blue)';

        // pill สถานะ (เขียว = ใช้งานอยู่, แดง = หมดอายุ)
        const ok = d.ok === '1';
        const pill = document.getElementById('vmStatus');
        document.getElementById('vmStatusText').textContent = d.status || '';
        pill.classList.toggle('is-expired', !ok);
        // ไอคอน: ใช้งานอยู่ = เครื่องหมายถูก, หมดอายุ = นาฬิกา
        pill.querySelector('i').className = ok ? 'bi bi-check-lg' : 'bi bi-clock-history';

        // สร้าง QR แบบ WIFI ให้สแกนเชื่อมต่อได้เลย
        const box = document.getElementById('vmQr');
        box.innerHTML = '';
        const payload = 'WIFI:T:WPA;S:' + wifiEsc(d.ssid) + ';P:' + wifiEsc(d.pass) + ';;';
        const qr = qrcode(0, 'M');
        qr.addData(payload);
        qr.make();
        box.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    });

    // ปุ่ม copy ในกล่อง credential
    modal.querySelectorAll('.vm-copy').forEach((btn) => {
        btn.addEventListener('click', async function () {
            const val = document.getElementById(btn.dataset.copy).textContent;
            if (!val || val === '—') return;
            try {
                await navigator.clipboard.writeText(val);
            } catch (_) {
                const t = document.createElement('textarea');
                t.value = val; document.body.appendChild(t); t.select();
                document.execCommand('copy'); t.remove();
            }
            // feedback: เปลี่ยนเป็นเครื่องหมายถูกชั่วครู่
            const icon = btn.querySelector('i');
            const prev = icon.className;
            icon.className = 'bi bi-check-lg';
            btn.classList.add('is-copied');
            btn.setAttribute('title', copiedText);
            setTimeout(() => { icon.className = prev; btn.classList.remove('is-copied'); }, 1400);
        });
    });

    // ปุ่มพิมพ์ตั๋ว: clone การ์ดตั๋วไปไว้ iframe เอกสารใหม่แล้วสั่งพิมพ์ (เลี่ยงปัญหา print ของ modal)
    const printBtn = document.getElementById('vmPrint');
    if (printBtn) {
        const CSS_MAIN  = '<?= base_url('assets/css/style.css') ?>';
        const CSS_ICONS = '<?= base_url('assets/plugins/bootstrap-icons/bootstrap-icons.min.css') ?>';
        const CSS_FONTS = '<?= base_url('assets/fonts/fonts.css') ?>';

        printBtn.addEventListener('click', function () {
            const ticket = modal.querySelector('.vm-ticket-wrap');
            if (!ticket) return;

            const frame = document.createElement('iframe');
            frame.setAttribute('aria-hidden', 'true');
            frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
            // พิมพ์เมื่อ iframe (รวม stylesheet) โหลดเสร็จ แล้วลบทิ้ง
            frame.onload = function () {
                frame.contentWindow.focus();
                frame.contentWindow.print();
                setTimeout(function () { frame.remove(); }, 1000);
            };
            frame.srcdoc =
                '<!doctype html><html lang="th"><head><meta charset="utf-8">'
                + '<link rel="stylesheet" href="' + CSS_FONTS + '">'
                + '<link rel="stylesheet" href="' + CSS_ICONS + '">'
                + '<link rel="stylesheet" href="' + CSS_MAIN + '">'
                + '<style>'
                + '*{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
                + 'body{margin:0;background:#fff;padding:16px;}'
                + '.vm-ticket-wrap{padding:0;max-width:480px;margin:0 auto;}'
                + '.np-dot{display:none;}'
                + '.vm-cred-val{font-size:24px;}'
                + '@page{margin:14mm;}'
                + '</style></head><body>' + ticket.outerHTML + '</body></html>';
            document.body.appendChild(frame);
        });
    }
})();
</script>

<!-- DataTables server-side: ตารางประวัติ voucher -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const locSel  = document.getElementById('vfLoc');
    const statSel = document.getElementById('vfStatus');
    const dt = NetPass.dataTable('#voucherTable', {
        filters: '#vfToolbar',
        action: '#vfAction',
        ajax: {
            url: '<?= site_url('admin/voucher/data') ?>',
            data: function (d) { d.loc = locSel.value; d.status = statSel.value; }
        },
        order: [[5, 'desc']],   // เรียงตามวันที่ขอ ล่าสุดก่อน (คอลัมน์เลื่อน +1 จาก checkbox)
        columns: [
            { orderable: false, className: 'text-center' }, // checkbox
            { orderable: false },                       // ผู้ขอ
            { orderable: true },                        // username Wi-Fi
            { orderable: true },                        // พื้นที่
            { orderable: true },                        // ระยะเวลา
            { orderable: true },                        // วันที่ขอ
            { orderable: true },                        // หมดอายุ
            { orderable: true },                        // สถานะ
            { orderable: false, className: 'text-end' } // ปุ่มดู
        ]
    });
    locSel.addEventListener('change', function () { dt.ajax.reload(); });
    statSel.addEventListener('change', function () { dt.ajax.reload(); });

    // ───────── เลือกหลายใบ (ข้ามหน้า) → พิมพ์ตั๋ว 3 ใบ/แถว ─────────
    const selected    = new Set();
    const selPrintBtn = document.getElementById('vchPrintSel');
    const selCount    = document.getElementById('vchPrintCount');
    const selectAll   = document.getElementById('vchSelectAll');
    const tableEl     = document.getElementById('voucherTable');

    const CSS_MAIN  = '<?= base_url('assets/css/style.css') ?>';
    const CSS_ICONS = '<?= base_url('assets/plugins/bootstrap-icons/bootstrap-icons.min.css') ?>';
    const CSS_FONTS = '<?= base_url('assets/fonts/fonts.css') ?>';
    const TICKET_LBL = {
        title: <?= json_encode(lang('Voucher.cardTitle')) ?>,
        wifi:  <?= json_encode(lang('Voucher.lblWifiName')) ?>,
        loc:   <?= json_encode(lang('Voucher.colLocation')) ?>,
        dur:   <?= json_encode(lang('Voucher.colDuration')) ?>,
        exp:   <?= json_encode(lang('Voucher.colExpiresAt')) ?>,
        scan:  <?= json_encode(lang('Voucher.scanConnect')) ?>
    };

    function escHtml(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function wifiEsc(s) { return String(s || '').replace(/([\\;,:"])/g, '\\$1'); }
    function qrSvg(ssid, pass) {
        const qr = qrcode(0, 'M');
        qr.addData('WIFI:T:WPA;S:' + wifiEsc(ssid) + ';P:' + wifiEsc(pass) + ';;');
        qr.make();
        return qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    }
    function refreshSelUI() {
        selCount.textContent = selected.size;
        selPrintBtn.classList.toggle('d-none', selected.size === 0);
    }
    function syncSelectAll() {
        const boxes = tableEl.querySelectorAll('tbody input.vch-pick');
        selectAll.checked = boxes.length > 0 && [...boxes].every((b) => b.checked);
    }

    // ติ๊กในแถว (delegation — รองรับแถวที่ DataTables วาดใหม่)
    tableEl.addEventListener('change', function (e) {
        const cb = e.target.closest('input.vch-pick');
        if (!cb) return;
        if (cb.checked) selected.add(cb.value); else selected.delete(cb.value);
        refreshSelUI(); syncSelectAll();
    });
    // ติ๊กทั้งหน้า
    selectAll.addEventListener('change', function () {
        tableEl.querySelectorAll('tbody input.vch-pick').forEach(function (cb) {
            cb.checked = selectAll.checked;
            if (selectAll.checked) selected.add(cb.value); else selected.delete(cb.value);
        });
        refreshSelUI();
    });
    // วาดแถวใหม่ (เปลี่ยนหน้า/ค้นหา) → ติ๊กกลับให้ตามที่เลือกไว้
    dt.on('draw.dt', function () {
        tableEl.querySelectorAll('tbody input.vch-pick').forEach(function (cb) {
            cb.checked = selected.has(cb.value);
        });
        syncSelectAll(); refreshSelUI();
    });

    // สร้าง HTML การ์ดตั๋ว (ดีไซน์ vm-ticket เดิม) จากข้อมูล 1 ใบ
    function buildTicket(t) {
        return '<div class="vm-ticket-wrap"><div class="vm-ticket">'
            + '<div class="vm-ticket-main">'
            +   '<div class="vm-ticket-title"><i class="bi bi-wifi"></i><span>' + escHtml(TICKET_LBL.title) + '</span></div>'
            +   '<div class="vm-meta">'
            +     '<div class="vm-meta-col"><div class="vm-meta-label">' + escHtml(TICKET_LBL.wifi) + '</div><div class="vm-meta-val font-mono">' + escHtml(t.ssid) + '</div></div>'
            +     '<div class="vm-meta-col"><div class="vm-meta-label">' + escHtml(TICKET_LBL.loc) + '</div><div class="vm-meta-val text-truncate">' + escHtml(t.loc) + '</div></div>'
            +   '</div>'
            +   '<div class="vm-creds">'
            +     '<div class="vm-cred"><div class="vm-cred-top"><span class="vm-cred-label">USERNAME</span></div><div class="vm-cred-val font-mono">' + escHtml(t.user) + '</div></div>'
            +     '<div class="vm-cred"><div class="vm-cred-top"><span class="vm-cred-label">PASSWORD</span></div><div class="vm-cred-val font-mono">' + escHtml(t.pass) + '</div></div>'
            +   '</div>'
            +   '<div class="vm-foot"><span>' + escHtml(TICKET_LBL.dur) + ' <b>' + escHtml(t.dur) + '</b></span><span>' + escHtml(TICKET_LBL.exp) + ' <b>' + escHtml(t.expires) + '</b></span></div>'
            + '</div>'
            + '<div class="vm-ticket-qr"><div class="vm-qr-name font-mono">' + escHtml(t.ssid) + '</div><div class="vm-qr">' + qrSvg(t.ssid, t.pass) + '</div><div class="vm-qr-cap">' + escHtml(TICKET_LBL.scan) + '</div></div>'
            + '</div></div>';
    }

    // พิมพ์ตั๋วหลายใบ: grid 3 ใบ/แถว (ย่อด้วย zoom) ใน iframe
    function printTickets(tickets) {
        const cards = tickets.map(buildTicket).join('');
        const frame = document.createElement('iframe');
        frame.setAttribute('aria-hidden', 'true');
        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
        frame.onload = function () {
            frame.contentWindow.focus();
            frame.contentWindow.print();
            setTimeout(function () { frame.remove(); }, 1500);
        };
        frame.srcdoc =
            '<!doctype html><html lang="th"><head><meta charset="utf-8">'
            + '<link rel="stylesheet" href="' + CSS_FONTS + '">'
            + '<link rel="stylesheet" href="' + CSS_ICONS + '">'
            + '<link rel="stylesheet" href="' + CSS_MAIN + '">'
            + '<style>'
            + '*{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            + 'body{margin:0;background:#fff;}'
            + '.np-print-sheet{display:flex;flex-wrap:wrap;gap:4mm 1mm;align-content:flex-start;}'
            + '.np-print-sheet .vm-ticket-wrap{padding:0;width:600px;zoom:0.38;break-inside:avoid;}'
            + '.np-print-sheet .vm-cred-val{font-size:24px;}'
            + '@page{margin:1.27cm 1.27cm;size:A4 portrait;}'
            + '</style></head><body><div class="np-print-sheet">' + cards + '</div></body></html>';
        document.body.appendChild(frame);
    }

    // กดปุ่มพิมพ์ที่เลือก → ดึงข้อมูลตั๋วตาม id แล้วพิมพ์
    selPrintBtn.addEventListener('click', async function () {
        if (selected.size === 0) return;
        selPrintBtn.disabled = true;
        try {
            const url = '<?= site_url('admin/voucher/tickets') ?>?' + [...selected].map((id) => 'ids[]=' + encodeURIComponent(id)).join('&');
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (data.ok && data.tickets.length) { printTickets(data.tickets); }
        } catch (_) {} finally {
            selPrintBtn.disabled = false;
        }
    });
});
</script>
<?= $this->endSection() ?>
