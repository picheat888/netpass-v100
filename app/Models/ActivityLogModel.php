<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ActivityLogModel — ตาราง activity_logs (audit trail, append-only)
 * เขียนผ่าน App\Services\ActivityLog::record() เท่านั้น
 */
class ActivityLogModel extends Model
{
    protected $table         = 'activity_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'action',
        'actor_id',
        'actor_name',
        'actor_username',
        'actor_role',
        'target_type',
        'target_id',
        'target_label',
        'details',
        'ip_address',
    ];
}
