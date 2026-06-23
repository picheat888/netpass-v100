<?php

/**
 * Pager template — pager แบบ "ช่วงหน้า" (compact range pager) ของ NetPass
 *
 *   [«] [‹]   N of N   [›] [»]
 *   หน้าแรก/ก่อนหน้า  ตัวบอกหน้าปัจจุบัน  ถัดไป/หน้าสุดท้าย
 *
 * สร้าง URL ของแต่ละหน้าจาก request ปัจจุบันเอง (คง query string เดิม: filter/search/per_page)
 * จึงไม่ต้องพึ่ง getPrevious()/getNext() ของ PagerRenderer ที่ความหมาย/ความพร้อมต่างกันในแต่ละเวอร์ชัน
 * ใช้แค่ getCurrentPage()/getPageCount() ที่เสถียรทุกเวอร์ชัน
 */
$current = max(1, (int) $pager->getCurrentPageNumber());
$pages   = max(1, (int) $pager->getPageCount());

// สร้าง URL ไปยังหน้าที่ต้องการ โดยคง query string เดิมไว้ทั้งหมด
$req   = service('request');
$query = $req->getGet();
$base  = current_url();
$urlFor = static function (int $pageNum) use ($base, $query) {
    $query['page'] = $pageNum;
    return $base . '?' . http_build_query($query);
};

$onFirst = $current <= 1;
$onLast  = $current >= $pages;

// ปุ่มหนึ่งปุ่ม: ถ้าใช้ไม่ได้ (ขอบหน้า) แสดงเป็น <span> กดไม่ได้, ถ้าใช้ได้แสดงเป็น <a>
$btn = static function (string $icon, int $target, bool $disabled, string $label) use ($urlFor) {
    if ($disabled) {
        return '<span class="np-page-btn is-disabled" aria-hidden="true"><i class="bi ' . $icon . '"></i></span>';
    }
    return '<a class="np-page-btn" href="' . esc($urlFor($target), 'attr')
        . '" aria-label="' . esc($label, 'attr') . '"><i class="bi ' . $icon . '"></i></a>';
};
?>

<?php if ($pages > 1): ?>
<nav class="np-pager" aria-label="<?= esc(lang('Common.nextPage'), 'attr') ?>">
    <div class="np-page-group">
        <?= $btn('bi-chevron-double-left', 1, $onFirst, lang('Common.firstPage')) ?>
        <?= $btn('bi-chevron-left', $current - 1, $onFirst, lang('Common.prevPage')) ?>
    </div>

    <span class="np-page-stat" aria-current="page">
        <strong><?= $current ?></strong>
        <span class="np-page-of"><?= esc(lang('Common.pageOf')) ?></span>
        <?= $pages ?>
    </span>

    <div class="np-page-group">
        <?= $btn('bi-chevron-right', $current + 1, $onLast, lang('Common.nextPage')) ?>
        <?= $btn('bi-chevron-double-right', $pages, $onLast, lang('Common.lastPage')) ?>
    </div>
</nav>
<?php endif ?>
