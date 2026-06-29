<?php

namespace App\Services;

use App\Models\ActivityLogModel;
use Throwable;

/**
 * ActivityLog — ตัวเขียน audit log กลาง (เรียกจาก controller/event ทุกที่)
 * เติม actor (จากผู้ล็อกอิน) + ip ให้อัตโนมัติ, เก็บ details เป็น JSON
 * ห่อ try/catch — ถ้าเขียน log พลาด จะไม่ทำให้ action หลักล้ม
 */
class ActivityLog
{
    /**
     * บันทึก 1 event
     *
     * @param string $action เช่น 'location.update', 'voucher.request'
     * @param array  $opts   target_type, target_id, target_label, details(array),
     *                       และ override actor_* ได้ (ใช้ตอน login/logout ที่ส่ง user มาเอง)
     */
    public static function record(string $action, array $opts = []): void
    {
        try {
            $actor = self::actor($opts);

            (new ActivityLogModel())->insert([
                'action'         => $action,
                'actor_id'       => $actor['id'],
                'actor_name'     => $actor['name'],
                'actor_username' => $actor['username'],
                'actor_role'     => $actor['role'],
                'target_type'    => $opts['target_type']  ?? null,
                'target_id'      => $opts['target_id']    ?? null,
                'target_label'   => $opts['target_label'] ?? null,
                'details'        => isset($opts['details']) && $opts['details'] !== null
                    ? json_encode($opts['details'], JSON_UNESCAPED_UNICODE)
                    : null,
                'ip_address'     => service('request')->getIPAddress(),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'ActivityLog failed [' . $action . ']: ' . $e->getMessage());
        }
    }

    /**
     * หา actor: ใช้ค่า override ใน $opts ก่อน (เช่น user จาก event login)
     * ไม่งั้นดึงจากผู้ล็อกอินปัจจุบัน
     */
    private static function actor(array $opts): array
    {
        if (isset($opts['actor_id']) || isset($opts['actor_name'])) {
            return [
                'id'       => $opts['actor_id']       ?? null,
                'name'     => $opts['actor_name']     ?? null,
                'username' => $opts['actor_username'] ?? null,
                'role'     => $opts['actor_role']     ?? null,
            ];
        }

        $user = (function_exists('auth') && auth()->loggedIn()) ? auth()->user() : null;
        if (! $user) {
            return ['id' => null, 'name' => null, 'username' => null, 'role' => null];
        }

        return [
            'id'       => (int) $user->id,
            'name'     => self::displayName($user),
            'username' => $user->username,
            'role'     => $user->inGroup('admin') ? 'admin' : 'user',
        ];
    }

    /** ชื่อแสดงผล: firstname lastname, ถ้าว่างใช้ username */
    public static function displayName($user): string
    {
        $name = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));

        return $name !== '' ? $name : (string) ($user->username ?? '');
    }
}
