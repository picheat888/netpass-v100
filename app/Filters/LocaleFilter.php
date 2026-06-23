<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ตั้งภาษา (locale) ตามที่เก็บไว้ใน session ทุก request
 */
class LocaleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $supported = config('App')->supportedLocales;
        $locale    = null;

        // 1) ภาษาที่ผู้ใช้บันทึกไว้กับบัญชี (ถ้า login) — ติดตัวข้าม session/อุปกรณ์
        if (auth()->loggedIn()) {
            $userLocale = auth()->user()->locale ?? null;
            if (is_string($userLocale) && in_array($userLocale, $supported, true)) {
                $locale = $userLocale;
                session()->set('locale', $locale);   // sync session ให้ตรงกับโปรไฟล์
            }
        }

        // 2) ถ้ายังไม่ได้ → ใช้ค่าใน session (เช่น guest สลับที่หน้า login)
        if ($locale === null) {
            $sess = session('locale');
            if (is_string($sess) && in_array($sess, $supported, true)) {
                $locale = $sess;
            }
        }

        // 3) ถ้าไม่มีเลย → ปล่อยให้ใช้ defaultLocale ของระบบ (en) ตาม Config\App
        if ($locale !== null) {
            service('request')->setLocale($locale);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // ไม่ทำอะไร
    }
}
