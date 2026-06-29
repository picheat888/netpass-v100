// edit modal — เติมค่าจากปุ่มที่กด (รองรับแถวที่ DataTables สร้างใหม่)
const editForm     = document.getElementById('editForm');
const editUser     = document.getElementById('editUser');
const editPass     = document.getElementById('editPass');
const editSaveBtn  = document.getElementById('editSaveBtn');
let origUser = '', origPass = '';

document.getElementById('editModal').addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget; if (!b) return;
    editForm.action = NP_POOL_DETAIL.voucherBaseUrl + '/' + b.dataset.id + '/update';
    origUser = b.dataset.user || '';
    origPass = b.dataset.pass || '';
    editUser.value = origUser;
    editPass.value = origPass;
    refreshDirty();   // เปิดมายังไม่แก้ → ปุ่มบันทึก disabled
});

// เปิดปุ่มบันทึกเฉพาะเมื่อค่าต่างจากเดิม (ไม่มีการแก้ไข = disabled)
function refreshDirty() {
    editSaveBtn.disabled = editUser.value === origUser && editPass.value === origPass;
}
editUser.addEventListener('input', refreshDirty);
editPass.addEventListener('input', refreshDirty);

// กด Save → ไม่ submit ทันที เด้ง dialog ยืนยันก่อน (โชว์ค่าเดิม → ค่าใหม่)
const editConfirmModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editConfirmModal'));

// escape ข้อความก่อนยัดเป็น HTML
function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

// 1 แถวตารางเทียบค่าเดิม → ค่าใหม่ (username/password เป็น mono)
function diffRow(label, oldV, newV) {
    return '<tr>'
        + '<td class="dlg-cmp-field">' + esc(label) + '</td>'
        + '<td class="dlg-cmp-from font-mono">' + esc(oldV || '—') + '</td>'
        + '<td class="dlg-cmp-to font-mono">' + esc(newV || '—') + '</td>'
        + '</tr>';
}

// สร้างรายการเทียบเฉพาะช่องที่เปลี่ยน
function buildDiff() {
    const rows = [];
    if (editUser.value !== origUser) rows.push(diffRow(NP_POOL_DETAIL.i18n.username, origUser, editUser.value));
    if (editPass.value !== origPass) rows.push(diffRow(NP_POOL_DETAIL.i18n.password, origPass, editPass.value));
    document.getElementById('editDiffBody').innerHTML = rows.join('');
}

editForm.addEventListener('submit', function (e) {
    e.preventDefault();
    if (editSaveBtn.disabled) return;   // ไม่มีการแก้ไข — ไม่ทำอะไร
    buildDiff();
    editConfirmModal.show();
});
// ยืนยัน → submit จริง (native submit ไม่วนกลับเข้า interceptor)
document.getElementById('editConfirmBtn').addEventListener('click', function () {
    editForm.submit();
});

// delete voucher modal — เติม action + username จากปุ่มที่กด
document.getElementById('vDeleteModal').addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget; if (!b) return;
    document.getElementById('vDeleteForm').action = NP_POOL_DETAIL.voucherBaseUrl + '/' + b.dataset.id + '/delete';
    document.getElementById('vDeleteUser').textContent = b.dataset.user || '';
});

// ตาราง DataTables server-side
document.addEventListener('DOMContentLoaded', function () {
    const stSel = document.getElementById('stStatus');
    const dt = NetPass.dataTable('#poolDetailTable', {
        filters: '#stToolbar',
        ajax: {
            url: NP_POOL_DETAIL.dataUrl,
            data: function (d) { d.status = stSel.value; }
        },
        order: [],
        columns: [
            { orderable: false },                       // ลำดับ (#)
            { orderable: true },                        // username
            { orderable: false },                       // password
            { orderable: true },                        // ระยะเวลา
            { orderable: true },                        // สถานะ
            { orderable: true },                        // วันที่นำเข้า
            { orderable: false, className: 'text-end' } // จัดการ
        ]
    });
    stSel.addEventListener('change', function () { dt.ajax.reload(); });
});
