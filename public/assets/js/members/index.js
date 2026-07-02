// อ่านค่าจาก server ผ่าน data island
const NP_MEMBERS = JSON.parse(document.getElementById('np-members-data').textContent);

// เติมข้อมูลใน edit modal จาก data attribute ของปุ่ม
document.getElementById('editModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn) return;
    const d = btn.dataset;
    document.getElementById('editForm').action     = '/admin/members/' + d.id + '/update';
    document.getElementById('editEmail').value      = d.email || '';
    document.getElementById('editFirstname').value  = d.firstname || '';
    document.getElementById('editLastname').value   = d.lastname || '';
    document.getElementById('editUsername').value   = d.username || '';
    document.getElementById('editPosition').value   = d.position || '';
    // ตั้งค่า role ผ่าน Tom Select API
    const roleEl = document.getElementById('editRole');
    if (roleEl.tomselect) { roleEl.tomselect.setValue(d.role || 'user'); }
    else { roleEl.value = d.role || 'user'; }
    // เติมรูปปัจจุบันลงกล่อง avatar
    const box = document.getElementById('editAvatarBox');
    box.textContent = '';
    if (d.img) {
        const im = new Image();
        im.src = d.img;
        im.alt = '';
        box.appendChild(im);
    } else {
        const sp = document.createElement('span');
        sp.textContent = d.initial || '';
        box.appendChild(sp);
    }
    document.getElementById('editAvatarInput').value = '';
});

// เติม action + ชื่อใน delete modal
document.getElementById('mbDeleteModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn) return;
    document.getElementById('mbDeleteForm').action = '/admin/members/' + btn.dataset.id + '/delete';
    // ครอบชื่อด้วยเครื่องหมายคำพูด
    document.getElementById('mbDeleteName').textContent = btn.dataset.name ? '"' + btn.dataset.name + '"' : '';
});

// เติม action + username ใน reset-password modal
document.getElementById('mbResetModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn) return;
    document.getElementById('mbResetForm').action = '/admin/members/' + btn.dataset.id + '/reset-password';
    document.getElementById('rsUsername').value = btn.dataset.username || '';
    const pwd = document.getElementById('rsPwd');
    pwd.value = ''; pwd.type = 'password'; pwd.classList.remove('np-invalid');
    document.getElementById('rsForceChange').checked = true;
    document.getElementById('rsPwdRules').classList.remove('show');
    document.getElementById('rsPwdMeter').classList.remove('show');
    const e2 = document.querySelector('#mbResetForm .np-field-err'); if (e2) e2.remove();
});

// สลับสถานะบัญชี
document.getElementById('memberTable').addEventListener('change', function (e) {
    const sw = e.target.closest('input.mb-toggle');
    if (!sw) return;
    const activating = sw.checked;
    sw.checked = !activating;             // คืนค่าเดิมไว้ก่อน รอยืนยัน
    const id   = sw.dataset.id;
    const name = sw.dataset.name ? '"' + sw.dataset.name + '"' : '';
    const target = activating
        ? { form: 'mbActivateForm',   name: 'mbActivateName',   modal: 'mbActivateModal' }
        : { form: 'mbDeactivateForm', name: 'mbDeactivateName', modal: 'mbDeactivateModal' };
    document.getElementById(target.form).action = '/admin/members/' + id + '/toggle';
    document.getElementById(target.name).textContent = name;
    bootstrap.Modal.getOrCreateInstance(document.getElementById(target.modal)).show();
});

document.addEventListener('DOMContentLoaded', function () {
    // ── ตาราง DataTables server-side ──
    const grpSel = document.getElementById('mbGroup');
    const dt = NetPass.dataTable('#memberTable', {
        filters: '#mbToolbar',
        action: '#mbAction',
        ajax: {
            url: NP_MEMBERS.urls.data,
            data: function (d) { d.group = grpSel.value; }
        },
        order: [[0, 'asc']],
        columns: [
            { orderable: true },                        // ชื่อ-สกุล
            { orderable: true },                        // ตำแหน่ง
            { orderable: true },                        // Email
            { orderable: true },                        // Username
            { orderable: true },                        // Role
            { orderable: true },                        // สถานะ
            { orderable: false, className: 'text-end' } // จัดการ
        ]
    });
        window.memberDT = dt;
    grpSel.addEventListener('change', function () { dt.ajax.reload(); });
});

// ───────── รูปโปรไฟล์: เลือกไฟล์ → ครอป/ซูม → พรีวิว ─────────
window.addEventListener('load', function () {
    const cropEl  = document.getElementById('mbCropModal');
    const cropImg = document.getElementById('mbCropImg');
    const zoom    = document.getElementById('mbZoom');
    const bsCrop  = new bootstrap.Modal(cropEl);
    let cropper = null, objectUrl = null, prevZoom = 0, activeInput = null, activeBox = null;
    const BADTYPE = NP_MEMBERS.i18n.badType;

    function wireAvatar(inputId, boxId, hintId) {
        const input = document.getElementById(inputId);
        const box   = document.getElementById(boxId);
        const hint  = document.getElementById(hintId);
        const hintDefault = hint.innerHTML;
        input.addEventListener('change', function () {
            const file = input.files[0];
            if (!file) return;
            if (file.type !== 'image/jpeg' && file.type !== 'image/png') {
                hint.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + BADTYPE;
                hint.classList.add('is-error');
                input.value = '';
                return;
            }
            hint.classList.remove('is-error');
            hint.innerHTML = hintDefault;
            activeInput = input; activeBox = box;
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = URL.createObjectURL(file);
            cropImg.src = objectUrl;
            bsCrop.show();
        });
    }
    wireAvatar('addAvatarInput', 'addAvatarBox', 'addAvatarHint');
    wireAvatar('editAvatarInput', 'editAvatarBox', 'editAvatarHint');

    cropEl.addEventListener('shown.bs.modal', function () {
        prevZoom = 0; zoom.value = 0;
        cropper = new Cropper(cropImg, {
            aspectRatio: 1, viewMode: 1, dragMode: 'move', autoCropArea: 1,
            background: false, guides: false, highlight: false, center: false,
            cropBoxMovable: false, cropBoxResizable: false,
        });
    });
    zoom.addEventListener('input', function () {
        if (!cropper) return;
        const v = parseFloat(zoom.value);
        cropper.zoom(v - prevZoom);
        prevZoom = v;
    });
    cropEl.addEventListener('hidden.bs.modal', function () {
        if (cropper) { cropper.destroy(); cropper = null; }
        // เก็บกวาด backdrop ที่อาจค้าง
        setTimeout(function () {
            if (document.querySelector('.modal.show')) {
                document.body.classList.add('modal-open');
            }
        }, 200);
    });
    document.getElementById('mbCropApply').addEventListener('click', function () {
        if (!cropper || !activeInput) return;
        const type = (activeInput.files[0] && activeInput.files[0].type === 'image/png') ? 'image/png' : 'image/jpeg';
        const canvas = cropper.getCroppedCanvas({ width: 256, height: 256, imageSmoothingQuality: 'high' });
        canvas.toBlob(function (blob) {
            if (blob) {
                const name = 'avatar.' + (type === 'image/png' ? 'png' : 'jpg');
                const dtf = new DataTransfer();
                dtf.items.add(new File([blob], name, { type: type }));
                activeInput.files = dtf.files;
            }
            if (activeBox) { activeBox.innerHTML = '<img src="' + canvas.toDataURL(type) + '" alt="">'; }
            if (activeInput && activeInput.id === 'editAvatarInput') { markEditAvatarChanged(); }
            bsCrop.hide();
        }, type, 0.9);
    });

    // ───────── แถบวัด + เช็คลิสต์รหัสผ่าน + ปุ่มสุ่ม ─────────
    const newPwd     = document.getElementById('addPwd');
    const rulesBox   = document.getElementById('addPwdRules');
    const meter      = document.getElementById('addPwdMeter');
    const meterLabel = document.getElementById('addPwdMeterLabel');
    const STRENGTH = NP_MEMBERS.i18n.strength;
    const tests = {
        len:    function (v) { return v.length >= 8; },
        upper:  function (v) { return /[A-Z]/.test(v); },
        lower:  function (v) { return /[a-z]/.test(v); },
        number: function (v) { return /[0-9]/.test(v); },
        symbol: function (v) { return /[^A-Za-z0-9]/.test(v); },
    };
    function evaluate() {
        const v = newPwd.value;
        let passed = 0;
        rulesBox.querySelectorAll('li').forEach(function (li) {
            const ok = tests[li.dataset.rule](v);
            if (ok) passed++;
            li.classList.toggle('ok', ok);
            li.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        });
        let s = 0;
        if (v.length === 0)    s = 0;
        else if (passed <= 2)  s = 1;
        else if (passed === 3) s = 2;
        else if (passed === 4) s = 3;
        else                   s = 4;
        meter.dataset.lvl = s;
        meterLabel.textContent = STRENGTH[s];
    }
    function showPwdUi() { rulesBox.classList.add('show'); meter.classList.add('show'); }
    if (newPwd) {
        newPwd.addEventListener('input', function () {
            if (newPwd.value.length >= 1) { showPwdUi(); }
            else { rulesBox.classList.remove('show'); meter.classList.remove('show'); }
            evaluate();
        });
    }

    // ปุ่มสุ่มรหัสผ่าน
    function npGenPassword(len) {
        const U = 'ABCDEFGHJKLMNPQRSTUVWXYZ', L = 'abcdefghijkmnpqrstuvwxyz', D = '23456789', S = '!@#$%^&*?-_=+';
        const all = U + L + D + S;
        const pick = function (set) { return set.charAt(Math.floor(Math.random() * set.length)); };
        const out = [pick(U), pick(L), pick(D), pick(S)];
        for (let i = out.length; i < len; i++) { out.push(pick(all)); }
        for (let i = out.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); const t = out[i]; out[i] = out[j]; out[j] = t; }
        return out.join('');
    }
    const randomBtn = document.getElementById('addRandomPwd');
    if (randomBtn && newPwd) {
        randomBtn.addEventListener('click', function () {
            newPwd.value = npGenPassword(12);
            newPwd.type = 'text';   // โชว์ให้เห็น
            const eye = newPwd.parentElement.querySelector('.np-pwd-toggle i');
            if (eye) { eye.className = 'bi bi-eye-slash'; }
            newPwd.classList.remove('np-invalid');
            const sib = newPwd.closest('.np-pwd-wrap').nextElementSibling;
            if (sib && sib.classList.contains('np-field-err')) { sib.remove(); }
            showPwdUi(); evaluate();
        });
    }

    // ───────── reset รหัสผ่าน: meter + checklist + สุ่ม ─────────
    function setupPwdField(pwd, rules, mt, mtLabel, rndBtn) {
        if (! pwd) { return; }
        function evalP() {
            const v = pwd.value; let passed = 0;
            rules.querySelectorAll('li').forEach(function (li) {
                const ok = tests[li.dataset.rule](v);
                if (ok) passed++;
                li.classList.toggle('ok', ok);
                li.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            });
            const s = v.length === 0 ? 0 : (passed <= 2 ? 1 : (passed === 3 ? 2 : (passed === 4 ? 3 : 4)));
            mt.dataset.lvl = s; mtLabel.textContent = STRENGTH[s];
        }
        function showUi() { rules.classList.add('show'); mt.classList.add('show'); }
        pwd.addEventListener('input', function () {
            if (pwd.value.length >= 1) { showUi(); } else { rules.classList.remove('show'); mt.classList.remove('show'); }
            evalP();
        });
        if (rndBtn) {
            rndBtn.addEventListener('click', function () {
                pwd.value = npGenPassword(12); pwd.type = 'text';
                const eye = pwd.parentElement.querySelector('.np-pwd-toggle i');
                if (eye) { eye.className = 'bi bi-eye-slash'; }
                pwd.classList.remove('np-invalid');
                const sib = pwd.closest('.np-pwd-wrap').nextElementSibling;
                if (sib && sib.classList.contains('np-field-err')) { sib.remove(); }
                showUi(); evalP();
            });
        }
    }
    setupPwdField(document.getElementById('rsPwd'), document.getElementById('rsPwdRules'),
                  document.getElementById('rsPwdMeter'), document.getElementById('rsPwdMeterLabel'),
                  document.getElementById('rsRandomPwd'));
    // ฟอร์มรีเซ็ต: ตรวจ inline แล้ว submit
    document.getElementById('mbResetForm').addEventListener('submit', function (e) {
        if (! npValidateForm(this)) { e.preventDefault(); }
    });

    // ───────── สรุปข้อมูลก่อนยืนยันสร้างสมาชิก ─────────
    const addForm    = document.getElementById('addForm');
    const addModalEl = document.getElementById('addModal');
    const bsAdd      = bootstrap.Modal.getOrCreateInstance(addModalEl);
    const sumModalEl = document.getElementById('mbSummaryModal');
    const bsSum      = new bootstrap.Modal(sumModalEl);
    const roleSel    = addForm.querySelector('select[name="role"]');
    const FORCE_NOTE = NP_MEMBERS.i18n.forceNote;
    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function buildSummary() {
        const g = function (n) { const el = addForm.querySelector('[name="' + n + '"]'); return el ? el.value.trim() : ''; };
        const rows = [
            [NP_MEMBERS.i18n.colName,  (g('firstname') + ' ' + g('lastname')).trim()],
            [NP_MEMBERS.i18n.email,    g('email') || '—'],
            [NP_MEMBERS.i18n.position, g('position') || '—'],
            [NP_MEMBERS.i18n.role,     roleSel.options[roleSel.selectedIndex].text],
        ];
        document.getElementById('sumRows').innerHTML = rows.map(function (r) {
            return '<tr><td class="dlg-cmp-field">' + r[0] + '</td><td class="dlg-cmp-val">' + escapeHtml(r[1]) + '</td></tr>';
        }).join('');
        document.getElementById('sumUser').textContent = g('username');
        document.getElementById('sumPass').textContent = g('password');
        const note = document.getElementById('sumForceNote');
        if (document.getElementById('addForceChange').checked) {
            note.style.display = ''; note.innerHTML = '<i class="bi bi-shield-lock"></i> ' + FORCE_NOTE;
        } else { note.style.display = 'none'; note.innerHTML = ''; }
    }
    let addConfirmed = false;
    addForm.addEventListener('submit', function (e) {
        if (addConfirmed) { return; }
        e.preventDefault();
        if (! npValidateForm(addForm)) { return; }
        buildSummary();
        bsSum.show();
    });
    // OK = สร้างจริง
    document.getElementById('mbSummaryConfirm').addEventListener('click', function () {
        addConfirmed = true;
        addForm.submit();
    });
    sumModalEl.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const val = document.getElementById(btn.dataset.copy).textContent;
            if (! val) { return; }
            try { await navigator.clipboard.writeText(val); } catch (_) {}
            const icon = btn.querySelector('i'); const prev = icon.className;
            icon.className = 'bi bi-check-lg'; setTimeout(function () { icon.className = prev; }, 1300);
        });
    });

    // ───────── แก้ไขสมาชิก: ตรวจการเปลี่ยนแปลง + review ก่อนบันทึก ─────────
    const editFormEl   = document.getElementById('editForm');
    const editModalEl  = document.getElementById('editModal');
    const editSaveBtn  = document.getElementById('editSaveBtn');
    const bsEditReview = new bootstrap.Modal(document.getElementById('mbEditReviewModal'));
    const ROLE_LBL  = NP_MEMBERS.i18n.roleLbl;
    const ADMIN_LBL = NP_MEMBERS.i18n.adminLbl;
    const USER_LBL  = NP_MEMBERS.i18n.userLbl;
    const PHOTO_LBL = NP_MEMBERS.i18n.photoLbl;
    const NOCHANGE  = NP_MEMBERS.i18n.noChange;
    let editSnapshot = null, editAvatarChanged = false, editConfirmed = false;

    function markEditAvatarChanged() { editAvatarChanged = true; updateEditSaveState(); }
    function editVals() {
        return {
            email:     editFormEl.email.value.trim(),
            firstname: editFormEl.firstname.value.trim(),
            lastname:  editFormEl.lastname.value.trim(),
            position:  editFormEl.position.value.trim(),
            role:      editFormEl.role.value,
        };
    }
    function editChanged() {
        if (! editSnapshot) { return false; }
        if (editAvatarChanged) { return true; }
        const v = editVals();
        return v.email !== editSnapshot.email || v.firstname !== editSnapshot.firstname
            || v.lastname !== editSnapshot.lastname || v.position !== editSnapshot.position
            || v.role !== editSnapshot.role;
    }
    function updateEditSaveState() { editSaveBtn.disabled = ! editChanged(); }

    // snapshot ตอน modal แสดงเต็มที่
    editModalEl.addEventListener('shown.bs.modal', function () {
        editSnapshot = editVals();
        editAvatarChanged = false;
        editConfirmed = false;
        updateEditSaveState();
    });
    editFormEl.addEventListener('input', updateEditSaveState);
    editFormEl.addEventListener('change', updateEditSaveState);

    function roleLabel(v) { return v === 'admin' ? ADMIN_LBL : USER_LBL; }
    function changeRow(label, from, to) {
        return '<tr><td class="dlg-cmp-field">' + label + '</td>'
            + '<td class="dlg-cmp-from">' + escapeHtml(from) + '</td>'
            + '<td class="dlg-cmp-to">' + escapeHtml(to) + '</td></tr>';
    }
    function buildEditReview() {
        const v = editVals(); let html = '';
        if (v.email !== editSnapshot.email)         { html += changeRow(NP_MEMBERS.i18n.email,     editSnapshot.email || '—', v.email || '—'); }
        if (v.firstname !== editSnapshot.firstname) { html += changeRow(NP_MEMBERS.i18n.firstName, editSnapshot.firstname || '—', v.firstname || '—'); }
        if (v.lastname !== editSnapshot.lastname)   { html += changeRow(NP_MEMBERS.i18n.lastName,  editSnapshot.lastname || '—', v.lastname || '—'); }
        if (v.position !== editSnapshot.position)   { html += changeRow(NP_MEMBERS.i18n.position,  editSnapshot.position || '—', v.position || '—'); }
        if (v.role !== editSnapshot.role)           { html += changeRow(ROLE_LBL, roleLabel(editSnapshot.role), roleLabel(v.role)); }
        if (editAvatarChanged)                      { html += changeRow(PHOTO_LBL, '—', NP_MEMBERS.i18n.uploadNew); }
        document.getElementById('editReviewRows').innerHTML = html || ('<tr><td colspan="3" class="text-muted">' + NOCHANGE + '</td></tr>');
    }
    editFormEl.addEventListener('submit', function (e) {
        if (editConfirmed) { return; }
        e.preventDefault();
        if (! npValidateForm(editFormEl)) { return; }
        if (! editChanged()) { return; }     // ไม่มีการเปลี่ยน → ไม่ทำอะไร
        buildEditReview();
        bsEditReview.show();
    });
    document.getElementById('mbEditReviewConfirm').addEventListener('click', function () {
        editConfirmed = true;
        editFormEl.submit();
    });

    // ปุ่ม eye เปิด/ปิดดูรหัสผ่าน
    document.querySelectorAll('.np-modal-member .np-pwd-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const inp = btn.parentElement.querySelector('input');
            const reveal = inp.type === 'password';
            inp.type = reveal ? 'text' : 'password';
            btn.querySelector('i').className = reveal ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    // เปิด modal เดิมกลับมาเมื่อ validation ฝั่ง server ไม่ผ่าน
    if (NP_MEMBERS.openModal === 'add') {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addModal')).show();
    } else if (NP_MEMBERS.openModal === 'edit') {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
    }
});

// เริ่มพิมพ์ในช่อง → ลบกรอบแดง + ข้อความ error ทันที
document.querySelectorAll('.np-modal-member .form-control').forEach(function (inp) {
    inp.addEventListener('input', function () {
        inp.classList.remove('np-invalid');
        const wrap = inp.closest('.np-pwd-wrap') || inp;
        const msg = wrap.nextElementSibling;
        if (msg && msg.classList.contains('np-field-err')) { msg.remove(); }
    });
});

// ── client-side validation: เตือน inline ในตัว modal ──
function npAddErr(inp, message) {
    inp.classList.add('np-invalid');
    const anchor = inp.closest('.np-pwd-wrap') || inp;
    let sib = anchor.nextElementSibling;
    if (sib && sib.classList.contains('np-field-err')) sib.remove();
    const p = document.createElement('p');
    p.className = 'np-field-err';
    p.innerHTML = '<i class="bi bi-info-circle-fill"></i> ' + message;
    anchor.insertAdjacentElement('afterend', p);
}
function npValidateForm(form) {
    let ok = true;
    // ล้าง error เดิม
    form.querySelectorAll('.np-invalid').forEach(function (i) { i.classList.remove('np-invalid'); });
    form.querySelectorAll('.np-field-err').forEach(function (m) { if (!m.dataset.server) m.remove(); });
    // ช่องบังคับว่าง
    form.querySelectorAll('[data-req]').forEach(function (inp) {
        if (inp.value.trim() === '') { ok = false; npAddErr(inp, inp.dataset.reqmsg); }
    });
    // อีเมล: ตรวจ format
    const em = form.querySelector('input[name="email"]');
    if (em && em.value.trim() !== '' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(em.value.trim())) {
        ok = false; npAddErr(em, em.dataset.fmtmsg);
    }
    // รหัสผ่าน: ความแข็งแรง
    const pwd = form.querySelector('[data-weakmsg]');
    if (pwd && pwd.value.trim() !== '') {
        const strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(pwd.value);
        if (!strong) { ok = false; npAddErr(pwd, pwd.dataset.weakmsg); }
    }
    if (!ok) { const first = form.querySelector('.np-invalid'); if (first) first.focus(); }
    return ok;
}

// ───────── นำเข้าสมาชิก: upload → JSON → result modal → reload ตาราง ─────────
(function () {
    const form = document.getElementById('importForm');
    if (!form) return;
    const btn       = document.getElementById('importSubmit');
    const fileErr   = document.getElementById('importErr');
    const fileInput = document.getElementById('importFile');
    const drop      = document.getElementById('importDrop');
    const chip      = document.getElementById('importFileChip');
    const chipName  = document.getElementById('importFileName');
    const chipSize  = document.getElementById('importFileSize');
    const tokenName = NP_MEMBERS.csrf.name;
    const NO_FILE   = NP_MEMBERS.i18n.noFile;
    const BAD_FILE  = NP_MEMBERS.i18n.badFile;
    const locale    = document.documentElement.lang === 'th' ? 'th' : 'en';
    let failDT = null;

    function showErr(msg) {
        fileErr.querySelector('span').textContent = msg;
        fileErr.classList.remove('d-none');
    }
    function setLoading(on) {
        btn.disabled = on;
        btn.querySelector('.np-btn-label').classList.toggle('d-none', on);
        btn.querySelector('.spinner-border').classList.toggle('d-none', !on);
    }
    function updateCsrf(token) {
        if (!token) return;
        const input = form.querySelector('input[name="' + tokenName + '"]');
        if (input) input.value = token;
    }
    // bytes → อ่านง่าย (KB/MB)
    function fmtSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
    // เลือกไฟล์แล้ว → โชว์ file chip แทน dropzone
    function reflectFile() {
        const f = fileInput.files && fileInput.files[0];
        if (f) {
            chipName.textContent = f.name;
            chipSize.textContent = fmtSize(f.size);
            drop.classList.add('d-none');
            chip.classList.remove('d-none');
            fileErr.classList.add('d-none');
        } else {
            drop.classList.remove('d-none');
            chip.classList.add('d-none');
        }
    }
    function resetDrop() {
        fileInput.value = '';
        reflectFile();
        fileErr.classList.add('d-none');
    }

    // ── dropzone interaction ──
    // คลิก dropzone → เปิด file picker
    drop.addEventListener('click', function (e) {
        // กัน re-entrancy
        if (e.target === fileInput) return;
        fileInput.click();
    });
    fileInput.addEventListener('change', reflectFile);
    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); drop.classList.add('is-drag'); });
    });
    drop.addEventListener('dragleave', function (e) {
        if (e.target === drop) drop.classList.remove('is-drag');   // เอาไฮไลต์ออก
    });
    drop.addEventListener('drop', function (e) {
        e.preventDefault(); e.stopPropagation();
        drop.classList.remove('is-drag');
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;   // ใส่ไฟล์ที่ลากเข้า input
            reflectFile();
        }
    });
    document.getElementById('importFileRemove').addEventListener('click', resetDrop);
    // เปิด modal ใหม่ → ล้างสถานะ dropzone
    document.getElementById('importModal').addEventListener('hidden.bs.modal', resetDrop);

    // ── ตารางแถวพลาดใน result ──
    function renderFailures(failures) {
        const wrap   = document.getElementById('resFailWrap');
        const dialog = document.querySelector('#resultModal .modal-dialog');
        const rows = (failures || []).map(function (f) { return [f.row, f.username || '—', f.reason]; });
        if (!rows.length) {
            wrap.classList.add('d-none');
            dialog.classList.remove('is-wide');   // สำเร็จล้วน → กล่องแคบ
            if (failDT) { failDT.clear().draw(); }
            return;
        }
        dialog.classList.add('is-wide');          // มีแถวพลาด → ขยายกล่อง
        if (failDT) {
            failDT.clear().rows.add(rows).draw();
        } else {
            failDT = new DataTable('#resFailTable', {
                data: rows,
                paging: true, pageLength: 10, lengthChange: false,
                searching: false, ordering: false, info: true, autoWidth: false,
                columns: [
                    { className: 'text-center np-fail-row', width: '56px' },   // แถวที่
                    { className: 'np-fail-user', width: '34%' },               // username (mono)
                    { className: 'np-fail-reason' }                            // เหตุผล (ตัดบรรทัดได้)
                ],
                language: locale === 'th'
                    ? { info: 'แสดง _START_–_END_ จาก _TOTAL_', infoEmpty: 'ทั้งหมด 0', emptyTable: 'ไม่มีข้อมูล', paginate: { first: '«', previous: '‹', next: '›', last: '»' } }
                    : { info: 'Showing _START_–_END_ of _TOTAL_', infoEmpty: '0 items', emptyTable: 'No data', paginate: { first: '«', previous: '‹', next: '›', last: '»' } }
            });
        }
        wrap.classList.remove('d-none');
    }
    // คำนวณความกว้างคอลัมน์ใหม่หลัง modal โชว์
    document.getElementById('resultModal').addEventListener('shown.bs.modal', function () {
        if (failDT) { failDT.columns.adjust(); }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        fileErr.classList.add('d-none');
        if (!fileInput.files || !fileInput.files.length) { showErr(NO_FILE); return; }
        setLoading(true);
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            updateCsrf(json.csrf);
            if (json.error) { showErr(json.error); return; }

            // เติม result modal
            document.getElementById('resPercent').textContent = json.percent + '%';
            document.getElementById('resRing').style.setProperty('--pct', json.percent);
            document.getElementById('resSuccess').textContent = json.success;
            document.getElementById('resFail').textContent = json.failed;
            renderFailures(json.failures);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('importModal')).hide();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('resultModal')).show();
            resetDrop();
            if (window.memberDT) window.memberDT.ajax.reload(null, false);
        })
        .catch(function () { showErr(BAD_FILE); })
        .finally(function () { setLoading(false); });
    });
})();
