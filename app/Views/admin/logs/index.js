document.addEventListener('DOMContentLoaded', function () {
    const I = NP_LOGS.i18n;
    const actSel = document.getElementById('lgAction');
    const fromEl = document.getElementById('lgFrom');
    const toEl   = document.getElementById('lgTo');

    // ตาราง DataTables server-side — ส่งตัวกรอง action + ช่วงวันที่ไปกับ ajax
    const dt = NetPass.dataTable('#logTable', {
        filters: '#lgToolbar',
        action: '#lgActionBar',
        ajax: {
            url: NP_LOGS.urls.data,
            data: function (d) {
                d.action    = actSel.value;
                d.date_from = fromEl.value;
                d.date_to   = toEl.value;
            }
        },
        order: [[0, 'desc']],   // ล่าสุดก่อน
        columns: [
            { orderable: true },                        // เวลา
            { orderable: true },                        // ผู้ใช้
            { orderable: true },                        // สิทธิ์
            { orderable: true },                        // Username
            { orderable: true },                        // การกระทำ
            { orderable: true },                        // ประเภท
            { orderable: true },                        // รายการ
            { orderable: false },                       // รายละเอียด
            { orderable: true },                        // IP
            { orderable: false, className: 'text-end' } // ดู
        ]
    });
    const exportErr = document.getElementById('lgExportErr');
    function clearExportErr() {
        exportErr.classList.add('d-none');
        fromEl.classList.remove('is-invalid');
        toEl.classList.remove('is-invalid');
    }

    [actSel, fromEl, toEl].forEach(function (el) {
        el.addEventListener('change', function () { dt.ajax.reload(); });
    });
    // เลือกวันครบ → ล้าง error
    [fromEl, toEl].forEach(function (el) {
        el.addEventListener('change', function () {
            if (fromEl.value && toEl.value) { clearExportErr(); }
        });
    });

    // ส่งออก CSV — บังคับเลือกช่วงวันที่ (From + To) ก่อน
    document.getElementById('lgExport').addEventListener('click', function () {
        if (! fromEl.value || ! toEl.value) {
            exportErr.classList.remove('d-none');
            fromEl.classList.toggle('is-invalid', ! fromEl.value);
            toEl.classList.toggle('is-invalid', ! toEl.value);
            (! fromEl.value ? fromEl : toEl).focus();
            return;
        }
        clearExportErr();
        const q = new URLSearchParams({
            action:    actSel.value,
            date_from: fromEl.value,
            date_to:   toEl.value,
            search:    dt.search() || ''
        });
        // ใช้ <a download> แทน window.location — กัน progress bar (beforeunload) ค้าง เพราะ download ไม่ navigate
        const a = document.createElement('a');
        a.href = NP_LOGS.urls.export + '?' + q.toString();
        a.setAttribute('download', '');
        document.body.appendChild(a);
        a.click();
        a.remove();
    });

    // ───────── modal รายละเอียด ─────────
    const modalEl = document.getElementById('logDetailModal');
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const bodyEl  = document.getElementById('logDetailBody');

    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    // ทำ key ให้อ่านง่าย: location_id → Location id
    function humanize(k) {
        const s = String(k).replace(/_/g, ' ');
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    // แปลงค่าเป็นข้อความ: array → คั่นด้วย ,  | boolean → yes/no | object → JSON
    function fmt(v) {
        if (v === null || v === undefined || v === '') { return '—'; }
        if (Array.isArray(v)) { return v.join(', '); }
        if (typeof v === 'boolean') { return v ? 'yes' : 'no'; }
        if (typeof v === 'object') { return JSON.stringify(v); }
        return String(v);
    }

    // ตาราง key/value (ค่าเดียว)
    function kvTable(obj) {
        const rows = Object.keys(obj).map(function (k) {
            return '<tr><td class="dlg-cmp-field">' + esc(humanize(k)) + '</td>'
                + '<td class="dlg-cmp-val">' + esc(fmt(obj[k])) + '</td></tr>';
        }).join('');
        return '<table class="dlg-cmp-table"><tbody>' + rows + '</tbody></table>';
    }

    // ตารางเทียบ before → after (รวม key จากทั้งสองฝั่ง)
    function diffTable(before, after) {
        const keys = Array.from(new Set(Object.keys(before || {}).concat(Object.keys(after || {}))));
        const rows = keys.map(function (k) {
            return '<tr><td class="dlg-cmp-field">' + esc(humanize(k)) + '</td>'
                + '<td class="dlg-cmp-from">' + esc(fmt((before || {})[k])) + '</td>'
                + '<td class="dlg-cmp-to">' + esc(fmt((after || {})[k])) + '</td></tr>';
        }).join('');
        return '<table class="dlg-cmp-table"><thead><tr><th>' + esc(I.field) + '</th><th>'
            + esc(I.before) + '</th><th>' + esc(I.after) + '</th></tr></thead><tbody>' + rows + '</tbody></table>';
    }

    // ตารางรายชื่อผู้รับ voucher (guests[])
    function guestTable(guests) {
        const rows = guests.map(function (g) {
            return '<tr><td class="dlg-cmp-val">' + esc(g.name || '—') + '</td>'
                + '<td class="dlg-cmp-val font-mono">' + esc(g.phone || '—') + '</td>'
                + '<td class="dlg-cmp-val font-mono">' + esc(g.username || '—') + '</td></tr>';
        }).join('');
        return '<div class="np-log-section">' + esc(I.guestList) + '</div>'
            + '<table class="dlg-cmp-table"><thead><tr><th>' + esc(I.gName) + '</th><th>'
            + esc(I.gPhone) + '</th><th>' + esc(I.gUser) + '</th></tr></thead><tbody>' + rows + '</tbody></table>';
    }

    // เลือกวิธีแสดงตามรูปร่างของ details
    function renderDetails(d) {
        if (! d || (typeof d === 'object' && Object.keys(d).length === 0)) {
            return '<p class="text-muted mb-0">' + esc(I.noDetails) + '</p>';
        }
        let html = '';
        // before/after
        if (d.before && d.after) {
            html += diffTable(d.before, d.after);
            const rest = {};
            Object.keys(d).forEach(function (k) { if (k !== 'before' && k !== 'after') { rest[k] = d[k]; } });
            if (Object.keys(rest).length) { html += kvTable(rest); }
            return html;
        }
        // flat + guests[]
        const flat = {};
        let guests = null;
        Object.keys(d).forEach(function (k) {
            if (k === 'guests' && Array.isArray(d[k])) { guests = d[k]; } else { flat[k] = d[k]; }
        });
        if (Object.keys(flat).length) { html += kvTable(flat); }
        if (guests && guests.length) { html += guestTable(guests); }
        return html || ('<p class="text-muted mb-0">' + esc(I.noDetails) + '</p>');
    }

    // เปิด modal จากปุ่มดู (delegation — รองรับแถวที่ DataTables วาดใหม่)
    document.querySelector('#logTable').addEventListener('click', function (e) {
        const btn = e.target.closest('.np-log-view');
        if (! btn) { return; }
        document.getElementById('logDetailAction').textContent = btn.dataset.action || '';
        document.getElementById('logDetailMeta').textContent   = (btn.dataset.actor || '') + ' · ' + (btn.dataset.time || '');
        let details = null;
        try { details = btn.dataset.details ? JSON.parse(btn.dataset.details) : null; } catch (_) { details = null; }
        bodyEl.innerHTML = renderDetails(details);
        bsModal.show();
    });
});
