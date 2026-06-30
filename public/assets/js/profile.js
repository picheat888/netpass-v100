// โปรไฟล์: ครอป/ซูมรูป avatar, เช็คลิสต์+ความแข็งแรงรหัสผ่าน, toggle ดูรหัส, ล้าง error ตอนพิมพ์
// ข้อความภาษาอ่านจาก data island #np-profile-data (ตั้งใน view; CSP-safe)
window.addEventListener('load', function () {
    const dataEl = document.getElementById('np-profile-data');
    const L = (dataEl ? JSON.parse(dataEl.textContent).lang : null) || {};

    const input = document.getElementById('avatarInput');
    const box   = document.getElementById('avatarBox');
    const hint  = document.getElementById('avatarHint');
    if (!input) return;

    const hintDefault = hint.innerHTML;
    const viewBox = document.getElementById('avatarViewBox');
    const viewEl  = document.getElementById('avatarViewModal');
    const bsView  = bootstrap.Modal.getOrCreateInstance(viewEl);
    const modalEl = document.getElementById('avatarCropModal');
    const cropImg = document.getElementById('avatarCropImg');
    const zoom    = document.getElementById('avatarZoom');
    const bsModal = new bootstrap.Modal(modalEl);
    let cropper = null, objectUrl = null, prevZoom = 0;
    let avatarChanged = false;      // เปลี่ยนรูปใหม่หรือยัง (ใช้คุมปุ่ม Save)
    let syncSave = function () {};  // อัปเดตสถานะปุ่ม Save (กำหนดจริงในบล็อกปุ่ม Save)

    input.addEventListener('change', function () {
        const file = input.files[0];
        if (!file) return;

        // รับเฉพาะ JPG / PNG
        if (file.type !== 'image/jpeg' && file.type !== 'image/png') {
            hint.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + L.avatarBadType;
            hint.classList.add('is-error');
            input.value = '';
            return;
        }
        hint.classList.remove('is-error');
        hint.innerHTML = hintDefault;

        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        cropImg.src = objectUrl;
        bsView.hide();      // ปิด dialog แสดงรูป
        bsModal.show();     // เปิดหน้าจอ crop
    });

    // เริ่ม cropper เมื่อ modal แสดงเต็มที่ (กรอบจัตุรัสกลางภาพ ลากเลื่อน/ซูมรูปได้)
    modalEl.addEventListener('shown.bs.modal', function () {
        prevZoom = 0; zoom.value = 0;
        cropper = new Cropper(cropImg, {
            aspectRatio: 1, viewMode: 1, dragMode: 'move', autoCropArea: 1,
            background: false, guides: false, highlight: false, center: false,
            cropBoxMovable: false, cropBoxResizable: false,
        });
    });

    // แถบเลื่อน = ซูมแบบสัมพัทธ์ (เลื่อนขวา = ซูมเข้า)
    zoom.addEventListener('input', function () {
        if (!cropper) return;
        const v = parseFloat(zoom.value);
        cropper.zoom(v - prevZoom);
        prevZoom = v;
    });

    // ปิด modal → ล้าง cropper + เก็บกวาด backdrop ที่อาจค้างจากการสลับ 2 modal
    modalEl.addEventListener('hidden.bs.modal', function () {
        if (cropper) { cropper.destroy(); cropper = null; }
        setTimeout(function () {
            if (!document.querySelector('.modal.show')) {
                document.querySelectorAll('.modal-backdrop').forEach(function (b) { b.remove(); });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.body.style.removeProperty('overflow');
            }
        }, 250);
    });

    // ใช้รูปนี้ → ครอปเป็น 256×256 แล้วแทนไฟล์ในฟอร์ม + พรีวิวบน avatar
    document.getElementById('avatarCropApply').addEventListener('click', function () {
        if (!cropper) return;
        const type = (input.files[0] && input.files[0].type === 'image/png') ? 'image/png' : 'image/jpeg';
        const canvas = cropper.getCroppedCanvas({ width: 256, height: 256, imageSmoothingQuality: 'high' });
        canvas.toBlob(function (blob) {
            if (blob) {
                const name = 'avatar.' + (type === 'image/png' ? 'png' : 'jpg');
                const dt = new DataTransfer();
                dt.items.add(new File([blob], name, { type: type }));
                input.files = dt.files;
            }
            const preview = '<img src="' + canvas.toDataURL(type) + '" alt="">';
            box.innerHTML = preview;          // อัปเดต avatar บนแถบหัว
            viewBox.innerHTML = preview;      // อัปเดตรูปใน dialog ด้วย
            bsModal.hide();
            avatarChanged = true;             // มีการเปลี่ยนรูป → เปิดปุ่ม Save
            syncSave();
        }, type, 0.9);
    });

    // เช็คลิสต์ + แถบวัดความแข็งแรงรหัสผ่าน — เรียลไทม์ตอนพิมพ์
    const newPwd     = document.querySelector('input[name="new_password"]');
    const rulesBox   = document.getElementById('pwdRules');
    const meter      = document.getElementById('pwdMeter');
    const meterLabel = document.getElementById('pwdMeterLabel');
    if (newPwd && rulesBox) {
        const tests = {
            len:    function (v) { return v.length >= 8; },
            upper:  function (v) { return /[A-Z]/.test(v); },
            lower:  function (v) { return /[a-z]/.test(v); },
            number: function (v) { return /[0-9]/.test(v); },
            symbol: function (v) { return /[^A-Za-z0-9]/.test(v); },
        };
        // ระดับความแข็งแรง: นับเงื่อนไขที่ผ่าน → 0..4 (อ่อน/พอใช้/ดี/แข็งแรง)
        const STRENGTH = [
            { lvl: 0, label: '' },
            { lvl: 1, label: L.pwdWeak },
            { lvl: 2, label: L.pwdFair },
            { lvl: 3, label: L.pwdGood },
            { lvl: 4, label: L.pwdStrong },
        ];

        function evaluate() {
            const v = newPwd.value;
            let passed = 0;
            rulesBox.querySelectorAll('li').forEach(function (li) {
                const ok = tests[li.dataset.rule](v);
                if (ok) { passed++; }
                li.classList.toggle('ok', ok);
                li.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            });
            // map จำนวนเงื่อนไขที่ผ่าน → ระดับ (1-2=อ่อน, 3=พอใช้, 4=ดี, 5=แข็งแรง)
            let s = 0;
            if (v.length === 0)      { s = 0; }
            else if (passed <= 2)    { s = 1; }
            else if (passed === 3)   { s = 2; }
            else if (passed === 4)   { s = 3; }
            else                     { s = 4; }
            if (meter) {
                meter.dataset.lvl = s;
                meterLabel.textContent = STRENGTH[s].label;
            }
        }

        function show() { rulesBox.classList.add('show'); if (meter) meter.classList.add('show'); }
        function hide() { rulesBox.classList.remove('show'); if (meter) meter.classList.remove('show'); }

        // แสดงเช็คลิสต์/แถบความแข็งแรงเมื่อพิมพ์ไปแล้วอย่างน้อย 1 ตัวอักษร (ไม่ใช่แค่ focus)
        newPwd.addEventListener('input', function () {
            if (newPwd.value.length >= 1) { show(); } else { hide(); }
            evaluate();
            checkMatch();
            syncPwdBtn();
        });

        // ── แจ้งเตือนเรียลไทม์: รหัสยืนยันตรง/ไม่ตรง ──
        const confirmPwd = document.querySelector('input[name="confirm_password"]');
        const confirmMsg = document.getElementById('confirmMsg');
        const MATCH_OK  = L.matchOk;
        const MATCH_BAD = L.matchBad;
        function checkMatch() {
            if (!confirmPwd || !confirmMsg) { return; }
            if (confirmPwd.value === '') {
                confirmMsg.className = 'np-match-msg';
                confirmMsg.innerHTML = '';
            } else if (confirmPwd.value === newPwd.value) {
                confirmMsg.className = 'np-match-msg is-ok';
                confirmMsg.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + MATCH_OK;
            } else {
                confirmMsg.className = 'np-match-msg is-bad';
                confirmMsg.innerHTML = '<i class="bi bi-info-circle-fill"></i> ' + MATCH_BAD;
            }
        }
        if (confirmPwd) { confirmPwd.addEventListener('input', function () { checkMatch(); syncPwdBtn(); }); }

        // ── ปุ่ม Change password: เปิดเฉพาะเมื่อกรอกครบ 3 ช่อง + รหัสใหม่ผ่านเกณฑ์ + ยืนยันตรง ──
        const curPwd = document.querySelector('input[name="current_password"]');
        const pwdBtn = document.getElementById('pwdSubmitBtn');
        function syncPwdBtn() {
            if (!pwdBtn) { return; }
            const v = newPwd.value;
            const allRules = Object.keys(tests).every(function (k) { return tests[k](v); });
            const filled   = !!(curPwd && curPwd.value !== '' && v !== '' && confirmPwd && confirmPwd.value !== '');
            const matched  = !!(confirmPwd && confirmPwd.value === v);
            pwdBtn.disabled = ! (filled && allRules && matched);
        }
        if (curPwd) { curPwd.addEventListener('input', syncPwdBtn); }

        // ถ้ามีค่าค้างไว้หลัง validation error → แสดงผลทันทีโดยไม่ต้องพิมพ์ใหม่
        if (newPwd.value.length >= 1) { show(); evaluate(); }
        checkMatch();
        syncPwdBtn();   // เริ่มต้น: ปิดปุ่มจนกว่าจะกรอกครบ
    }

    // ── ปุ่ม Save (ข้อมูลโปรไฟล์): เปิดเฉพาะเมื่อมีการแก้ค่า หรือเปลี่ยนรูป ──
    const saveBtn = document.getElementById('profSaveBtn');
    if (saveBtn) {
        const watch = ['email', 'firstname', 'lastname']
            .map(function (n) { return document.querySelector('[name="' + n + '"]'); })
            .filter(Boolean);
        const initial = watch.map(function (f) { return f.value; });
        syncSave = function () {
            const changed = avatarChanged || watch.some(function (f, i) { return f.value !== initial[i]; });
            saveBtn.disabled = ! changed;
        };
        watch.forEach(function (f) { f.addEventListener('input', syncSave); });
        syncSave();   // เริ่มต้น: ปิดปุ่มจนกว่าจะมีการแก้
    }

    // ปุ่ม eye เปิด/ปิดดูรหัสผ่าน (ทุกช่องรหัสผ่าน)
    document.querySelectorAll('.np-pwd-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const inp = btn.parentElement.querySelector('input');
            const reveal = inp.type === 'password';
            inp.type = reveal ? 'text' : 'password';
            btn.querySelector('i').className = reveal ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    // เริ่มพิมพ์ในช่องที่มี error ค้างอยู่ → ลบข้อความ error + กรอบแดงทันที
    document.querySelectorAll('.np-prof-card .form-control').forEach(function (inp) {
        inp.addEventListener('input', function () {
            inp.classList.remove('np-invalid');
            const anchor = inp.closest('.np-pwd-wrap') || inp;
            const msg = anchor.nextElementSibling;
            if (msg && msg.classList.contains('np-field-err')) { msg.remove(); }
        }, { once: true });
    });
});
