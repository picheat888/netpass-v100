// NetPass — JS ส่วนกลาง
document.addEventListener('DOMContentLoaded', function () {
    var shell  = document.querySelector('.np-shell');
    var toggle = document.getElementById('npSidebarToggle');

    if (toggle && shell) {
        toggle.addEventListener('click', function () {
            // toggle sidebar (มือถือ = เลื่อนเข้า/ออก, จอใหญ่ = ย่อ/ขยาย)
            if (window.innerWidth < 768) {
                shell.classList.toggle('np-open');
            } else {
                shell.classList.toggle('np-collapsed');
            }
        });
    }

    var backdrop = document.querySelector('.np-backdrop');
    if (backdrop && shell) {
        backdrop.addEventListener('click', function () {
            shell.classList.remove('np-open');
        });
    }

    // select[data-autosubmit] → submit ฟอร์มเมื่อเปลี่ยนค่า
    document.addEventListener('change', function (e) {
        var sel = e.target.closest('select[data-autosubmit]');
        if (sel && sel.form) { sel.form.submit(); }
    });

    // แถบ progress ด้านบน ตอนนำทาง/โหลดหน้า
    var bar = document.createElement('div');
    bar.className = 'np-progress';
    document.body.appendChild(bar);
    var barTimer;
    function startBar() {
        clearTimeout(barTimer);
        bar.classList.add('active');
        bar.style.width = '0';
        // ค่อยๆ คืบไป ~90%
        requestAnimationFrame(function () { bar.style.width = '90%'; });
    }
    function finishBar() {
        bar.style.width = '100%';
        barTimer = setTimeout(function () {
            bar.classList.remove('active');
            bar.style.width = '0';
        }, 280);
    }
    // เริ่มเมื่อคลิกลิงก์ภายใน หรือ submit ฟอร์ม
    document.addEventListener('click', function (e) {
        var anchor = e.target.closest('a[href]');
        if (! anchor) { return; }
        if (anchor.target === '_blank' || anchor.hasAttribute('download') || anchor.dataset.bsToggle) { return; }
        var href = anchor.getAttribute('href');
        if (! href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) { return; }
        if (anchor.origin && anchor.origin !== location.origin) { return; }
        startBar();
    }, true);
    // เริ่มแถบเมื่อ submit ฟอร์ม POST จริง (ข้ามถ้าถูก preventDefault)
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) { return; }
        if (e.target && e.target.method && e.target.method.toLowerCase() === 'post') { startBar(); }
    });
    window.addEventListener('beforeunload', startBar);
    // หน้าใหม่โหลดเสร็จ → ปิดแถบ
    window.addEventListener('pageshow', finishBar);
});

/* ============================================================
   Dialog ซ้อน Dialog — จัด z-index ให้ถูกชั้น
   ============================================================ */
(function () {
    document.addEventListener('show.bs.modal', function (e) {
        // จำนวน modal ที่เปิดอยู่ก่อนหน้า
        var openCount = document.querySelectorAll('.modal.show').length;
        if (openCount < 1) { return; }                 // เป็น dialog แรก → ปล่อยปกติ

        var zIndex = 1055 + openCount * 30;
        e.target.style.zIndex = zIndex;
        // จัด z-index backdrop ของ dialog นี้
        window.setTimeout(function () {
            var bds = document.querySelectorAll('.modal-backdrop');
            if (! bds.length) { return; }
            var top = bds[bds.length - 1];
            top.style.zIndex = zIndex - 10;                  // อยู่เหนือ dialog ชั้นล่าง
            for (var i = 0; i < bds.length - 1; i++) {  // ซ่อน backdrop ชั้นล่าง
                bds[i].style.display = 'none';
            }
        }, 0);
    });

    document.addEventListener('hidden.bs.modal', function () {
        // ปิด dialog ชั้นบน → คืน backdrop ที่เหลือให้แสดง
        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) { backdrop.style.display = ''; });
        if (document.querySelectorAll('.modal.show').length) {
            document.body.classList.add('modal-open');
        }
    });

    // กด Esc ตอนมี dialog ซ้อน → ปิดเฉพาะตัวบนสุด
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        var open = document.querySelectorAll('.modal.show');
        if (open.length < 2) { return; }                 // ตัวเดียว → ปล่อย Bootstrap จัดการ
        e.stopPropagation();
        e.preventDefault();
        var top = null, maxZ = -1;
        open.forEach(function (modal) {
            var zIndex = parseInt(getComputedStyle(modal).zIndex, 10) || 0;
            if (zIndex >= maxZ) { maxZ = zIndex; top = modal; }
        });
        if (top) {
            var inst = bootstrap.Modal.getInstance(top);
            if (inst) { inst.hide(); }
        }
    }, true);
})();

/* ============================================================
   NetPass.dataTable — helper กลางสำหรับตาราง DataTables (server-side)
   ============================================================ */
window.NetPass = window.NetPass || {};
(function (NP) {

    // ข้อความ DataTables ตามภาษา
    var LANG = {
        th: {
            processing: 'กำลังโหลด…',
            search: '', searchPlaceholder: 'ค้นหา…',
            lengthMenu: 'แถวต่อหน้า _MENU_',
            info: 'แสดง _START_–_END_ จาก _TOTAL_ รายการ',
            infoEmpty: 'ทั้งหมด 0 รายการ',
            infoFiltered: '(กรองจาก _MAX_)',
            zeroRecords: 'ไม่พบข้อมูลที่ค้นหา',
            emptyTable: 'ยังไม่มีข้อมูล',
            paginate: { first: '«', previous: '‹', next: '›', last: '»' }
        },
        en: {
            processing: 'Loading…',
            search: '', searchPlaceholder: 'Search…',
            lengthMenu: 'Rows per page _MENU_',
            info: 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty: '0 items total',
            infoFiltered: '(filtered from _MAX_)',
            zeroRecords: 'No matching records found',
            emptyTable: 'No data',
            paginate: { first: '«', previous: '‹', next: '›', last: '»' }
        }
    };

    // สร้าง DataTable server-side พร้อมค่า default ของระบบ
    NP.dataTable = function (selector, opts) {
        opts = opts || {};
        var locale = document.documentElement.lang === 'th' ? 'th' : 'en';

        // layout toolbar
        var filtersNode = opts.filters ? document.querySelector(opts.filters) : null;
        var actionNode  = opts.action ? document.querySelector(opts.action) : null;

        // pager ที่ render เอง
        var pager = document.createElement('div');
        pager.className = 'np-dtpager';

        var layout = {
            topStart: filtersNode ? ['search', filtersNode] : 'search',
            topEnd: actionNode || null,
            bottomStart: ['pageLength', 'info'],
            bottomEnd: pager   // ใช้ pager ของเราเอง
        };
        delete opts.filters;
        delete opts.action;

        // info: ข้อความ "แสดง N–N จาก N"
        function infoText(start, end, total, max) {
            var wrapNum = function (value) { return '<span class="np-info-n">' + value + '</span>'; };
            if (total === 0) {
                return locale === 'th' ? 'ทั้งหมด ' + wrapNum(0) + ' รายการ' : wrapNum(0) + ' items';
            }
            var text = locale === 'th'
                ? 'แสดง ' + wrapNum(start + '–' + end) + ' จาก ' + wrapNum(total) + ' รายการ'
                : 'Showing ' + wrapNum(start + '–' + end) + ' of ' + wrapNum(total);
            if (max && total < max) {  // กำลังกรองอยู่
                text += ' <span class="np-info-filtered">' + (locale === 'th' ? '(กรองจาก ' + wrapNum(max) + ')' : '(filtered from ' + wrapNum(max) + ')') + '</span>';
            }
            return text;
        }

        var dt = new DataTable(selector, Object.assign({
            serverSide: true,
            processing: true,
            searchDelay: 350,
            lengthMenu: [10, 20, 50, 100],
            pageLength: 10,
            autoWidth: false,
            paging: true,
            language: LANG[locale],
            infoCallback: function (settings, start, end, max, total, pre) {
                return infoText(start, end, total, max);
            },
            layout: layout
        }, opts));

        // วาด pager เอง
        function btn(label, target, opt) {
            opt = opt || {};
            var cls = 'np-pg-btn' + (opt.active ? ' active' : '') + (opt.disabled ? ' is-disabled' : '');
            return '<button type="button" class="' + cls + '"' + (opt.disabled ? ' disabled' : '')
                + ' data-pg="' + target + '">' + label + '</button>';
        }
        function renderPager() {
            var info  = dt.page.info();
            var p     = info.page;
            var pages = info.pages;
            if (pages <= 0) { pager.innerHTML = ''; return; }
            var html  = '';
            html += btn('«', 0, { disabled: p === 0 });
            html += btn('‹', p - 1, { disabled: p === 0 });
            var start = Math.max(0, Math.min(p - 1, pages - 3));   // หน้าต่าง 3 เลข
            for (var i = start; i < Math.min(pages, start + 3); i++) {
                html += btn(i + 1, i, { active: i === p });
            }
            html += btn('›', p + 1, { disabled: p >= pages - 1 });
            html += btn('»', pages - 1, { disabled: p >= pages - 1 });
            pager.innerHTML = html;
        }
        dt.on('draw.dt', renderPager);
        pager.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-pg]');
            if (btn && ! btn.disabled) { dt.page(parseInt(btn.dataset.pg, 10)).draw('page'); }
        });

        // แต่ง select "แถวต่อหน้า" ให้เป็น dropdown ธีม
        dt.on('init.dt', function () {
            NP.enhanceSelects(dt.table().container());
        });

        // เติม data-label ให้ทุกเซลล์ (สำหรับการ์ดมือถือ)
        var headerLabels = [];
        dt.on('draw.dt', function () {
            if (! headerLabels.length) {
                dt.table().header().querySelectorAll('th').forEach(function (th) {
                    headerLabels.push(th.textContent.trim());
                });
            }
            dt.table().body().querySelectorAll('tr').forEach(function (tr) {
                var cells = tr.children;
                for (var i = 0; i < cells.length; i++) {
                    if (cells[i].tagName === 'TD' && headerLabels[i]) {
                        cells[i].setAttribute('data-label', headerLabels[i]);
                    }
                }
            });
        });

        // skeleton shimmer ตอนกำลังโหลดข้อมูล
        var tableRow = dt.table().node().closest('.dt-layout-row') || dt.table().node().parentElement;
        if (tableRow) {
            tableRow.style.position = 'relative';
            var skel = document.createElement('div');
            skel.className = 'np-dt-skel';
            var widths = ['40%', '16%', '22%', '30%', '18%'];
            var html = '';
            for (var row = 0; row < 6; row++) {
                html += '<div class="np-skel-row">';
                for (var col = 0; col < 4; col++) {
                    html += '<span class="np-skel-bar" style="width:' + widths[(row + col) % widths.length] + '"></span>';
                }
                html += '</div>';
            }
            skel.innerHTML = html;
            tableRow.appendChild(skel);
            // โชว์/ซ่อน skeleton + สำรองความสูงให้แถวตาราง
            function toggleSkel(on) {
                skel.classList.toggle('show', on);
                tableRow.style.minHeight = on ? (skel.offsetHeight ? skel.scrollHeight + 'px' : '276px') : '';
            }
            toggleSkel(true);   // โหลดครั้งแรก: โชว์ทันที
            dt.on('processing.dt', function (e, settings, processing) { toggleSkel(!!processing); });
            dt.on('draw.dt', function () { toggleSkel(false); });
        }

        return dt;
    };

    // แทน native <select> ด้วย dropdown ที่ style ได้ (Tom Select)
    NP.enhanceSelects = function (root) {
        if (! window.TomSelect) { return; }
        (root || document).querySelectorAll('select.form-select').forEach(function (el) {
            if (el.tomselect || el.dataset.noTs !== undefined) { return; }
            var origId = el.id;
            new TomSelect(el, {
                controlInput: null,        // ไม่มีช่องพิมพ์ค้นหา
                allowEmptyOption: true,
                hideSelected: false
            });
            // a11y: ชี้ <label for> กลับไปที่ <select> เดิม
            if (origId) {
                var lbl = document.querySelector('label[for="' + origId + '-ts-control"]');
                if (lbl) { lbl.setAttribute('for', origId); }
            }
        });
    };

    // บันทึกการ์ดตั๋ว (.vm-ticket-wrap) เป็นรูป PNG
    NP.saveCardImage = async function (cardEl, filename) {
        if (! cardEl || ! window.htmlToImage) { return; }
        var holder = document.createElement('div');
        holder.className = 'np-capture';   // บังคับการ์ดเป็นแนวนอน
        holder.style.cssText = 'position:fixed;left:-99999px;top:0;width:730px;background:#fff;';
        holder.appendChild(cardEl.cloneNode(true));
        holder.querySelectorAll('.vm-copy').forEach(function (el) { el.remove(); });
        var titleIcon = holder.querySelector('.vm-ticket-title i');
        if (titleIcon) { titleIcon.outerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="#3b7ddd" viewBox="0 0 16 16" style="flex-shrink:0"><path d="M15.384 6.115a.485.485 0 0 0-.047-.736A12.444 12.444 0 0 0 8 3C5.259 3 2.723 3.882.663 5.379a.485.485 0 0 0-.048.736.518.518 0 0 0 .668.05A11.448 11.448 0 0 1 8 4c2.507 0 4.827.802 6.716 2.164.205.148.49.13.668-.049z"/><path d="M13.229 8.271c.216-.216.194-.578-.063-.745A9.456 9.456 0 0 0 8 6c-1.905 0-3.68.56-5.166 1.526a.48.48 0 0 0-.063.745.525.525 0 0 0 .652.065A8.46 8.46 0 0 1 8 7c1.689 0 3.24.5 4.576 1.336.206.132.48.108.653-.065zm-2.183 2.183c.226-.226.185-.605-.1-.75A6.473 6.473 0 0 0 8 9c-1.06 0-2.062.254-2.946.704-.285.145-.326.524-.1.75l.015.015c.16.16.408.19.611.09A5.478 5.478 0 0 1 8 10c.764 0 1.49.156 2.15.435.203.1.45.07.611-.09l.085-.092zM9.06 12.44c.196-.196.198-.52-.04-.66A1.99 1.99 0 0 0 8 11.5a1.99 1.99 0 0 0-1.02.28c-.238.14-.236.464-.04.66l.706.706a.5.5 0 0 0 .707 0l.707-.707z"/></svg>'; }
        document.body.appendChild(holder);
        try {
            await document.fonts.ready;
            var node = holder.querySelector('.vm-ticket-wrap');
            var dataUrl = await htmlToImage.toPng(node, { pixelRatio: 2, backgroundColor: '#ffffff' });
            var a = document.createElement('a');
            a.href = dataUrl;
            a.download = 'voucher-' + (filename || 'wifi') + '.png';
            a.click();
        } finally {
            holder.remove();
        }
    };

    // เปิดใช้กับ select ทั้งหน้าเมื่อโหลดเสร็จ
    document.addEventListener('DOMContentLoaded', function () {
        NP.enhanceSelects(document);
    });
})(window.NetPass);
