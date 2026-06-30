// อ่านค่าจาก server ผ่าน data island (CSP-safe — ไม่มี inline executable JS)
const NP_MYVOUCHER = JSON.parse(document.getElementById('np-myvoucher-data').textContent);

(function () {
    const modal = document.getElementById('voucherModal');
    if (!modal) return;

    const copiedText = NP_MYVOUCHER.i18n.copied;

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
        pill.querySelector('i').className = ok ? 'bi bi-check-lg' : 'bi bi-clock-history';

        // สร้าง QR แบบ WIFI ให้สแกนเชื่อมต่อได้เลย
        const box = document.getElementById('vmQr');
        box.innerHTML = '';
        // SSID เปิด (auth ที่ captive portal) → QR แค่พาเข้า Wi-Fi เปลือยๆ; กรอก voucher ที่พอร์ทัล
        const payload = 'WIFI:T:nopass;S:' + wifiEsc(d.ssid) + ';;';
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
            const icon = btn.querySelector('i');
            const prev = icon.className;
            icon.className = 'bi bi-check-lg';
            btn.classList.add('is-copied');
            btn.setAttribute('title', copiedText);
            setTimeout(() => { icon.className = prev; btn.classList.remove('is-copied'); }, 1400);
        });
    });

    // บันทึกการ์ดตั๋วเป็นรูป PNG จากที่แสดงในกล่องรายละเอียด (ใช้ helper กลาง NetPass.saveCardImage)
    const saveImgBtn = document.getElementById('vmSaveImg');
    if (saveImgBtn) {
        saveImgBtn.addEventListener('click', async function () {
            const card = modal.querySelector('.vm-ticket-wrap');
            if (!card) return;
            const orig = saveImgBtn.innerHTML;
            saveImgBtn.disabled = true;
            saveImgBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            try {
                const name = (document.getElementById('vmUser').textContent || 'wifi').trim();
                await NetPass.saveCardImage(card, name);
            } catch (e) {
                alert(NP_MYVOUCHER.i18n.genErr);
            } finally {
                saveImgBtn.disabled = false;
                saveImgBtn.innerHTML = orig;
            }
        });
    }
})();

document.addEventListener('DOMContentLoaded', function () {
    const locSel  = document.getElementById('vfLoc');
    const statSel = document.getElementById('vfStatus');
    const dt = NetPass.dataTable('#voucherTable', {
        filters: '#vfToolbar',
        action: '#vfAction',
        ajax: {
            url: NP_MYVOUCHER.urls.data,
            data: function (d) { d.loc = locSel.value; d.status = statSel.value; }
        },
        order: [[5, 'desc']],   // เรียงตามวันที่ขอ ล่าสุดก่อน
        columns: [
            { orderable: false, className: 'text-center' }, // checkbox
            { orderable: true },                        // ผู้จัดจำหน่าย
            { orderable: true },                        // username Wi-Fi
            { orderable: true },                        // พื้นที่
            { orderable: true },                        // ระยะเวลา
            { orderable: true },                        // วันที่ขอ
            { orderable: true },                        // หมดอายุ
            { orderable: true },                        // สถานะ
            { orderable: false, className: 'text-end' } // ปุ่มดู
        ]
    });
    // เปิดให้ request modal เรียก reload ตารางได้หลังขอ voucher สำเร็จ
    window.voucherDT = dt;
    locSel.addEventListener('change', function () { dt.ajax.reload(); });
    statSel.addEventListener('change', function () { dt.ajax.reload(); });

    // ───────── เลือกหลายใบ (ข้ามหน้า) → พิมพ์ตั๋ว 3 ใบ/แถว ─────────
    const selected    = new Set();
    const selPrintBtn = document.getElementById('vchPrintSel');
    const selCount    = document.getElementById('vchPrintCount');
    const selectAll   = document.getElementById('vchSelectAll');
    const tableEl     = document.getElementById('voucherTable');

    const CSS_MAIN  = NP_MYVOUCHER.assets.cssMain;
    const CSS_ICONS = NP_MYVOUCHER.assets.cssIcons;
    const CSS_FONTS = NP_MYVOUCHER.assets.cssFonts;
    const TICKET_LBL = {
        title: NP_MYVOUCHER.i18n.title,
        wifi:  NP_MYVOUCHER.i18n.wifi,
        loc:   NP_MYVOUCHER.i18n.loc,
        dur:   NP_MYVOUCHER.i18n.dur,
        exp:   NP_MYVOUCHER.i18n.exp,
        scan:  NP_MYVOUCHER.i18n.scan
    };

    function escHtml(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function wifiEsc(s) { return String(s || '').replace(/([\\;,:"])/g, '\\$1'); }
    function qrSvg(ssid, pass) {
        const qr = qrcode(0, 'M');
        qr.addData('WIFI:T:nopass;S:' + wifiEsc(ssid) + ';;');
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
    selectAll.addEventListener('change', function () {
        tableEl.querySelectorAll('tbody input.vch-pick').forEach(function (cb) {
            cb.checked = selectAll.checked;
            if (selectAll.checked) selected.add(cb.value); else selected.delete(cb.value);
        });
        refreshSelUI();
    });
    dt.on('draw.dt', function () {
        tableEl.querySelectorAll('tbody input.vch-pick').forEach(function (cb) {
            cb.checked = selected.has(cb.value);
        });
        syncSelectAll(); refreshSelUI();
    });

    // สร้าง HTML การ์ดตั๋ว (ดีไซน์ vm-ticket) จากข้อมูล 1 ใบ
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

    // พิมพ์ตั๋วหลายใบ: grid 3 ใบ/แถว ใน iframe
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
            const url = NP_MYVOUCHER.urls.tickets + '?' + [...selected].map((id) => 'ids[]=' + encodeURIComponent(id)).join('&');
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (data.ok && data.tickets.length) { printTickets(data.tickets); }
        } catch (_) {} finally {
            selPrintBtn.disabled = false;
        }
    });
});
