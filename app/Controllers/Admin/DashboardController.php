<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LocationModel;
use App\Models\VoucherIssueModel;
use App\Models\VoucherModel;

class DashboardController extends BaseController
{
    // แดชบอร์ดหลังบ้าน — รวมข้อมูลภาพรวมจาก DB
    public function index()
    {
        $voucherModel = new VoucherModel();
        $issueModel   = new VoucherIssueModel();
        $locModel     = new LocationModel();
        $db           = db_connect();

        // stat cards
        $remaining     = $voucherModel->where('status', 'instock')->countAllResults();
        $issuedToday   = $issueModel->issuedToday();
        $locationCount = $locModel->countAllResults();

        $weekStart  = date('Y-m-d', strtotime('-6 days')) . ' 00:00:00';
        $issuedWeek = $issueModel->where('issued_at >=', $weekStart)->countAllResults();

        // กราฟรายวัน 7 วันล่าสุด
        $rows = $db->table('voucher_issues')
            ->select('DATE(issued_at) AS d, COUNT(*) AS c')
            ->where('issued_at >=', $weekStart)
            ->groupBy('DATE(issued_at)')
            ->get()->getResultArray();
        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['d']] = (int) $row['c'];
        }
        $thDow = ['Sun' => 'อา', 'Mon' => 'จ', 'Tue' => 'อ', 'Wed' => 'พ', 'Thu' => 'พฤ', 'Fri' => 'ศ', 'Sat' => 'ส'];
        $isEn  = service('request')->getLocale() === 'en';
        $chart = [];
        $max   = 1;
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $val  = $byDate[$date] ?? 0;
            $max  = max($max, $val);
            $dow  = date('D', strtotime($date));
            $chart[] = ['val' => $val, 'label' => $isEn ? $dow : ($thDow[$dow] ?? $dow)];
        }
        // ความสูงแท่งเป็นเปอร์เซ็นต์เทียบค่าสูงสุด (เว้นหัว ~12% ให้ตัวเลขเหนือแท่งไม่ล้น)
        foreach ($chart as &$chartItem) {
            $chartItem['h'] = (int) round($chartItem['val'] / $max * 88);
        }
        unset($chartItem);

        // กิจกรรมล่าสุด
        $recent = $issueModel
            ->select('voucher_issues.*, locations.name AS loc_name, locations.name_en AS loc_name_en')
            ->join('locations', 'locations.id = voucher_issues.location_id', 'left')
            ->orderBy('voucher_issues.issued_at', 'DESC')
            ->findAll(6);

        // คงเหลือแยกตามพื้นที่
        $area = $db->table('locations l')
            ->select("l.id, l.name, l.name_en, COUNT(v.id) AS total, SUM(CASE WHEN v.status='instock' THEN 1 ELSE 0 END) AS instock")
            ->join('vouchers v', 'v.location_id = l.id', 'left')
            ->groupBy('l.id')
            ->orderBy('l.id')
            ->get()->getResultArray();

        return view('admin/dashboard/index', [
            'title'         => lang('Nav.dashboard'),
            'subtitle'      => lang('Nav.dashboardSub'),
            'active'        => 'dashboard',
            'remaining'     => $remaining,
            'issuedToday'   => $issuedToday,
            'locationCount' => $locationCount,
            'issuedWeek'    => $issuedWeek,
            'chart'         => $chart,
            'recent'        => $recent,
            'area'          => $area,
        ]);
    }
}
