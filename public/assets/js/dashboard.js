/**
 * Dashboard (admin) — นับเลขการ์ดสถิติจาก 0 → ค่าจริง ตอนโหลดหน้า
 * อ่านค่าเป้าหมายจาก data-count, เคารพ prefers-reduced-motion (ไม่มี PHP จึงแยกไฟล์ได้)
 */
document.addEventListener('DOMContentLoaded', function () {
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.querySelectorAll('.np-countup').forEach(function (el) {
        var target = parseInt(el.dataset.count || '0', 10);
        if (reduce || target <= 0) { el.textContent = target.toLocaleString(); return; }
        var dur = 900, startTs = null;
        el.textContent = '0';
        requestAnimationFrame(function step(ts) {
            if (startTs === null) { startTs = ts; }
            var p = Math.min((ts - startTs) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);   // ease-out cubic
            el.textContent = Math.round(target * eased).toLocaleString();
            if (p < 1) { requestAnimationFrame(step); }
        });
    });
});
