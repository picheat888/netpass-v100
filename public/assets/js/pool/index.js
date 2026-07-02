// อ่านค่าจาก server ผ่าน data island
const NP_POOL = JSON.parse(document.getElementById('np-pool-data').textContent);

// ตาราง DataTables server-side
document.addEventListener('DOMContentLoaded', function () {
    const poolDT = NetPass.dataTable('#poolTable', {
        action: '#poolAction',
        ajax: { url: NP_POOL.dataUrl },
        order: [[0, 'asc']],
        columns: [
            { orderable: true },  // พื้นที่
            { orderable: true },  // คงเหลือ
            { orderable: true },  // จ่ายแล้ว
            { orderable: true },  // รวม
            { orderable: false }  // ดู
        ]
    });

    // นำเข้า CSV
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
        const csrfField = impForm.querySelector('input[name="' + NP_POOL.csrfName + '"]');
        const BAD    = NP_POOL.badFile;
        const CNT    = NP_POOL.countUnit;

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
                if (data.ok) {
                    // สำเร็จ → ปิด modal, โชว์ผล, refresh ตาราง
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('importModal')).hide();
                    document.getElementById('poolResCount').textContent = CNT.replace('{0}', data.imported);
                    document.getElementById('poolResLoc').textContent   = data.location || '—';
                    document.getElementById('poolResDur').textContent   = data.duration || '—';
                    document.getElementById('poolResSsid').textContent  = data.ssid || '—';
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('poolResultModal')).show();
                    poolDT.ajax.reload(null, false);
                    input.value = ''; chip.classList.add('d-none'); drop.classList.remove('d-none'); clearErr();
                    return;
                }
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

// ───────── จัดการ Location ─────────
// เติมข้อมูลใน edit modal
document.getElementById('editModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn) return;   // เปิดเองตอน validation error
    document.getElementById('editForm').action   = '/admin/locations/' + btn.dataset.id + '/update';
    document.getElementById('editName').value    = btn.dataset.name;
    document.getElementById('editNameEn').value  = btn.dataset.nameEn;
    document.getElementById('editSsid').value    = btn.dataset.ssid;
});

// ปุ่ม Save ของ edit
(function () {
    const form    = document.getElementById('editForm');
    const saveBtn = document.getElementById('editSaveBtn');
    // ช่อง + label สำหรับรายการเทียบค่า
    const fields  = [
        { id: 'editNameEn', label: NP_POOL.i18n.nameEn },
        { id: 'editName',   label: NP_POOL.i18n.name },
        { id: 'editSsid',   label: NP_POOL.i18n.ssid },
    ];
    let snapshot = null;   // ค่าตอนเปิด modal
    let orig     = {};     // ค่าเดิมรายช่อง
    function vals() {
        return JSON.stringify(fields.map(function (f) {
            return document.getElementById(f.id).value.trim();
        }));
    }
    function update() { saveBtn.disabled = snapshot === null || vals() === snapshot; }
    document.getElementById('editModal').addEventListener('shown.bs.modal', function () {
        orig = {};
        fields.forEach(function (f) { orig[f.id] = document.getElementById(f.id).value; });
        snapshot = vals();
        update();
    });
    form.addEventListener('input', update);

    // ── confirm dialog ──
    const confirmModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('locEditConfirmModal'));
    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function diffRow(label, o, n) {
        return '<tr><td class="dlg-cmp-field">' + esc(label) + '</td>'
            + '<td class="dlg-cmp-from">' + esc(o || '—') + '</td>'
            + '<td class="dlg-cmp-to">' + esc(n || '—') + '</td></tr>';
    }
    function buildDiff() {
        const rows = [];
        fields.forEach(function (f) {
            const now = document.getElementById(f.id).value;
            if (now !== (orig[f.id] || '')) rows.push(diffRow(f.label, orig[f.id], now));
        });
        document.getElementById('locEditDiffBody').innerHTML = rows.join('');
    }
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (! npValidateForm(form)) return;   // ช่องบังคับว่าง
        if (saveBtn.disabled) return;          // ไม่มีการแก้ไข
        buildDiff();
        confirmModal.show();
    });
    document.getElementById('locEditConfirmBtn').addEventListener('click', function () {
        form.submit();   // ส่งจริง
    });
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
    if (NP_POOL.openModal === 'add') {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addModal')).show();
    } else if (NP_POOL.openModal === 'edit') {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
    }
});

// เริ่มพิมพ์ในช่อง → ลบ error
document.querySelectorAll('.np-modal .form-control').forEach(function (inp) {
    inp.addEventListener('input', function () {
        inp.classList.remove('np-invalid');
        var msg = inp.nextElementSibling;
        if (msg && msg.classList.contains('np-field-err')) { msg.remove(); }
    });
});

// ตรวจช่องบังคับ (data-req) ฝั่ง client
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
// add: validate inline แล้วปล่อย submit ปกติ
document.querySelectorAll('#addModal form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        if (! npValidateForm(form)) { e.preventDefault(); }
    });
});
