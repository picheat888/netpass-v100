<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$isEn    = service('request')->getLocale() === 'en';
$palette = ['#3B7DDD', '#0EA66B', '#E0930B', '#8B5CF6', '#E5484D', '#0E8FA6'];
$locName = static fn ($row) => $isEn ? (($row['loc_name_en'] ?? $row['name_en'] ?? '') ?: ($row['loc_name'] ?? $row['name'] ?? '')) : ($row['loc_name'] ?? $row['name'] ?? '');

// การ์ดสถิติ 4 ใบ — สี/ไอคอน + chip มุมขวา ตาม mockup
$stats = [
    ['icon' => 'bi-box-seam',         'color' => '#3B7DDD', 'soft' => '#E8F0FB', 'value' => $remaining,     'label' => lang('Dashboard.remaining'), 'sub' => lang('Dashboard.remainingSub'), 'trend' => lang('Dashboard.trendRemaining')],
    ['icon' => 'bi-wifi',             'color' => '#0EA66B', 'soft' => '#E6F6EF', 'value' => $issuedToday,   'label' => lang('Dashboard.today'),     'sub' => lang('Dashboard.todaySub'),     'trend' => lang('Dashboard.trendToday')],
    ['icon' => 'bi-geo-alt',          'color' => '#8B5CF6', 'soft' => '#F1ECFD', 'value' => $locationCount, 'label' => lang('Dashboard.locations'), 'sub' => lang('Dashboard.locationsSub'), 'trend' => lang('Dashboard.trendLocations')],
    ['icon' => 'bi-graph-up-arrow',   'color' => '#E0930B', 'soft' => '#FCF1DE', 'value' => $issuedWeek,    'label' => lang('Dashboard.week'),      'sub' => lang('Dashboard.weekSub'),      'trend' => lang('Dashboard.trendWeek')],
];
?>

<!-- STAT CARDS -->
<div class="row g-3">
    <?php foreach ($stats as $stat): ?>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="np-card np-card-pad h-100 np-card-hover">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="np-stat-icon" style="background:<?= $stat['soft'] ?>;color:<?= $stat['color'] ?>">
                        <i class="bi <?= $stat['icon'] ?>"></i>
                    </div>
                    <span class="np-chip"><?= esc($stat['trend']) ?></span>
                </div>
                <div class="np-stat-value"><?= esc(number_format((int) $stat['value'])) ?></div>
                <div class="np-stat-label"><?= esc($stat['label']) ?></div>
                <div class="np-stat-sub"><?= esc($stat['sub']) ?></div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<!-- CHART + BY AREA -->
<div class="row g-3 mt-1">
    <div class="col-lg-7">
        <div class="np-card np-card-pad h-100 d-flex flex-column">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <div class="np-section-title"><?= lang('Dashboard.chartTitle') ?></div>
                    <div class="np-section-sub"><?= lang('Dashboard.chartSub') ?></div>
                </div>
                <!-- ป้าย live (จุดเขียวกะพริบ) มุมขวาบนกราฟ ตาม mockup -->
                <span class="d-inline-flex align-items-center gap-2 fw-semibold"
                      style="font-size:12px;color:var(--np-ok-fg);background:var(--np-ok-bg);padding:5px 11px;border-radius:20px">
                    <span class="np-livedot"></span><?= lang('Dashboard.chartLive') ?>
                </span>
            </div>
            <!-- พื้นที่กราฟ: flex-grow เต็มความสูงการ์ด, แท่งสูงเป็น % (สเกลตามค่าสูงสุด), label แยกแถวด้านล่าง -->
            <div class="d-flex flex-column flex-grow-1">
                <div class="d-flex justify-content-between gap-2 flex-grow-1 position-relative" style="min-height:150px">
                    <span class="np-bar-gl" style="bottom:25%"></span>
                    <span class="np-bar-gl" style="bottom:50%"></span>
                    <span class="np-bar-gl" style="bottom:75%"></span>
                    <?php foreach ($chart as $point): ?>
                        <div class="flex-fill d-flex flex-column justify-content-end np-bar-col">
                            <span class="np-bar-val"><?= esc($point['val']) ?></span>
                            <div class="np-bar" style="width:100%;max-width:40px;margin:0 auto;height:<?= max(4, (int) $point['h']) ?>%;background:linear-gradient(180deg,#5B92E0,#3B7DDD);border-radius:8px 8px 4px 4px"></div>
                        </div>
                    <?php endforeach ?>
                </div>
                <div class="d-flex justify-content-between gap-2 mt-2">
                    <?php foreach ($chart as $point): ?>
                        <span class="flex-fill text-center np-stat-sub"><?= esc($point['label']) ?></span>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="np-card np-card-pad h-100">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div>
                    <div class="np-section-title mb-1"><?= lang('Dashboard.byArea') ?></div>
                    <div class="np-section-sub"><?= lang('Dashboard.byAreaSub') ?></div>
                </div>
                <?php if (count($area) > 5): ?>
                    <a href="<?= site_url('admin/pool') ?>" class="text-decoration-none fw-semibold"
                       style="font-size:13px;color:var(--np-primary)"><?= lang('Dashboard.viewAll') ?></a>
                <?php endif ?>
            </div>
            <?php foreach (array_slice($area, 0, 5) as $i => $areaRow): ?>
                <?php
                $total   = (int) $areaRow['total'];
                $instock = (int) $areaRow['instock'];
                $pct     = $total > 0 ? round($instock / $total * 100) : 0;
                $color   = $palette[$i % count($palette)];
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span style="font-size:13px;font-weight:600;color:var(--np-text-2)"><?= esc($locName($areaRow)) ?></span>
                        <span style="font-size:13px;font-weight:600;color:var(--np-text-2);font-feature-settings:'tnum'"><?= esc($instock) ?><span style="color:var(--np-muted-3);font-weight:500"> / <?= esc($total) ?></span></span>
                    </div>
                    <div class="progress" style="height:7px;background:#EEF1F6">
                        <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
</div>

<!-- RECENT ACTIVITY -->
<div class="np-card np-card-pad mt-3">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <div class="np-section-title mb-1"><?= lang('Dashboard.recent') ?></div>
            <div class="np-section-sub"><?= lang('Dashboard.recentSub') ?></div>
        </div>
        <a href="<?= site_url('admin/voucher') ?>" class="text-decoration-none fw-semibold"
           style="font-size:13px;color:var(--np-primary)"><?= lang('Dashboard.viewAll') ?></a>
    </div>

    <?php if (empty($recent)): ?>
        <div class="text-center np-stat-sub py-4"><?= lang('Dashboard.noActivity') ?></div>
    <?php else: ?>
        <div class="d-flex flex-column">
            <?php foreach ($recent as $row): ?>
                <?php $ok = ($row['status'] ?? '') === 'active'; ?>
                <div class="np-recent-item d-flex align-items-center gap-3">
                    <div class="np-avatar" style="width:38px;height:38px;font-size:13px;border-radius:12px"><i class="bi bi-ticket-perforated"></i></div>
                    <div class="flex-grow-1 min-w-0">
                        <div style="font-size:13.5px;font-weight:600" class="text-truncate">
                            <?= esc($row['guest_name'] ?: 'Guest user') ?>
                            <span class="np-stat-sub">· <?= esc($row['guest_voucher']) ?></span>
                        </div>
                        <div class="np-stat-sub"><?= esc($locName($row)) ?> · <?= esc(duration_label($row['duration'])) ?></div>
                    </div>
                    <div class="text-end">
                        <span class="np-chip <?= $ok ? 'np-chip-ok' : 'np-chip-danger' ?>"><?= $ok ? lang('Common.active') : lang('Common.expired') ?></span>
                        <div class="np-stat-sub mt-1"><?= esc(date('d/m H:i', strtotime($row['issued_at']))) ?></div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
