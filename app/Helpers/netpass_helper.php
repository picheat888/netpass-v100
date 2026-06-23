<?php

/**
 * NetPass helper — ฟังก์ชันสั้นๆ ใช้ซ้ำเกี่ยวกับ voucher/role
 */

if (! function_exists('voucher_durations')) {
    // คืน array durations ทั้งหมดจาก config
    function voucher_durations(): array
    {
        return config('Voucher')->durations;
    }
}

if (! function_exists('duration_label')) {
    // คืน label ของ duration ตามภาษาปัจจุบัน (th/en)
    function duration_label(string $key): string
    {
        $duration = config('Voucher')->durations[$key] ?? null;
        if ($duration === null) {
            return $key;
        }

        return service('request')->getLocale() === 'en' ? $duration['label_en'] : $duration['label'];
    }
}

if (! function_exists('duration_hours')) {
    // คืนจำนวนชั่วโมงของ duration (ใช้คำนวณ expires_at)
    function duration_hours(string $key): int
    {
        return (int) (config('Voucher')->durations[$key]['hours'] ?? 0);
    }
}

if (! function_exists('is_admin')) {
    // เช็คว่า user ที่ login อยู่เป็น admin หรือไม่
    function is_admin(): bool
    {
        return auth()->loggedIn() && auth()->user()->inGroup('admin');
    }
}

if (! function_exists('voucher_active')) {
    // voucher ใช้งานได้จริงหรือไม่ — สถานะ active และยังไม่ถึงวันหมดอายุ
    function voucher_active(?string $status, ?string $expiresAt): bool
    {
        return $status === 'active'
            && $expiresAt !== null && $expiresAt !== ''
            && strtotime($expiresAt) > time();
    }
}
