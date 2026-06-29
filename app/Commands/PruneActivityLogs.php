<?php

namespace App\Commands;

use App\Models\ActivityLogModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * logs:prune — ลบ activity_logs ที่เก่ากว่า N วัน (retention)
 * ใช้กับ cron ได้ เช่น รันรายเดือน: php spark logs:prune --days 365
 */
class PruneActivityLogs extends BaseCommand
{
    protected $group       = 'NetPass';
    protected $name        = 'logs:prune';
    protected $description = 'ลบ activity_logs ที่เก่ากว่า N วัน (ค่าเริ่มต้น 365)';
    protected $usage       = 'logs:prune [--days N]';
    protected $options     = ['--days' => 'จำนวนวันที่จะเก็บไว้ (ค่าเริ่มต้น 365)'];

    public function run(array $params)
    {
        $days = (int) (CLI::getOption('days') ?? 365);
        if ($days < 1) {
            $days = 365;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        $model  = new ActivityLogModel();

        // นับก่อน (countAllResults รีเซ็ต query ให้เอง)
        $count = $model->where('created_at <', $cutoff)->countAllResults();
        if ($count === 0) {
            CLI::write('ไม่มี log เก่ากว่า ' . $days . ' วัน — ไม่ต้องลบ', 'yellow');

            return;
        }

        // ลบผ่าน query builder (เลี่ยงเงื่อนไข delete ของ Model)
        db_connect()->table('activity_logs')->where('created_at <', $cutoff)->delete();

        CLI::write('ลบ activity_logs ' . $count . ' แถว (เก่ากว่า ' . $days . ' วัน — ก่อน ' . $cutoff . ')', 'green');
    }
}
