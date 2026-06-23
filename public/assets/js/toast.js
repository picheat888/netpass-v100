// Toast แจ้งผล — แสดง toast ทั้งหมดเมื่อโหลดเสร็จ (รอ Bootstrap พร้อม) แล้วซ่อนเองใน 3.5 วินาที
window.addEventListener('load', function () {
    document.querySelectorAll('.np-toast').forEach(function (el) {
        bootstrap.Toast.getOrCreateInstance(el, { delay: 3500 }).show();
    });
});
