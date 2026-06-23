<?php

/**
 * Footer มาตรฐานของ datatable — ใช้ซ้ำทุกหน้า list
 *
 * ตัวแปรที่รับ:
 *   $total   (int)              จำนวนรายการทั้งหมด
 *   $perPage (int|null)         จำนวนต่อหน้า — ใส่เมื่อหน้านี้ "แบ่งหน้า" (paginate)
 *   $pager   (PagerRenderer|null) ตัว pager จาก model->pager
 *
 * พฤติกรรม:
 *   - หน้าที่แบ่งหน้า  → ซ้าย: "แสดง a–b จาก N" + เลือกแถวต่อหน้า | ขวา: ปุ่มเลขหน้า
 *   - หน้าที่ไม่แบ่ง   → ซ้าย: "ทั้งหมด N รายการ" (โครงเดียวกัน ดูเป็นมาตรฐาน)
 */
$total     = (int) ($total ?? 0);
$perPage   = $perPage ?? null;
$pager     = $pager ?? null;
$paginated = $perPage !== null;

$req  = service('request');
$page = max(1, (int) ($req->getGet('page') ?: 1));

if ($paginated && $total > 0) {
    $from  = (($page - 1) * (int) $perPage) + 1;
    $to    = min($total, $page * (int) $perPage);
    $label = lang('Common.showing', [$from, $to, $total]);
} else {
    $label = lang('Common.totalItems', [$total]);
}

// คง query string เดิม (filter/search) ไว้ตอนเปลี่ยนแถวต่อหน้า — ยกเว้น page/per_page
$hidden = $req->getGet();
unset($hidden['page'], $hidden['per_page']);
?>
<div class="np-list-foot d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="np-foot-left d-flex align-items-center gap-3 flex-wrap">
        <?php if ($paginated): ?>
            <form method="get" class="np-perpage d-flex align-items-center gap-2 m-0">
                <?php foreach ($hidden as $field => $value): ?>
                    <?php if (is_array($value)) {
                        continue;
                    } ?>
                    <input type="hidden" name="<?= esc($field, 'attr') ?>" value="<?= esc($value, 'attr') ?>">
                <?php endforeach ?>
                <span><?= lang('Common.perPage') ?></span>
                <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <?php foreach ([10, 20, 50, 100] as $pp): ?>
                        <option value="<?= $pp ?>" <?= (int) $perPage === $pp ? 'selected' : '' ?>><?= $pp ?></option>
                    <?php endforeach ?>
                </select>
            </form>
        <?php endif ?>

        <span class="np-foot-range"><?= esc($label) ?></span>
    </div>

    <?php if ($pager): ?>
        <?= $pager->links('default', 'np_pager') ?>
    <?php endif ?>
</div>
