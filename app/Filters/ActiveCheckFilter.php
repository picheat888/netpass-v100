<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * เช็คสถานะ active ของผู้ใช้ทุก request —
 * Shield ตรวจ active แค่ตอน login; ถ้า admin ปิดใช้งานบัญชีระหว่างที่ session ยัง live
 * ผู้ใช้จะยังทำงานต่อได้ filter นี้บังคับ logout ทันทีเมื่อ active = 0
 */
class ActiveCheckFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! auth()->loggedIn()) {
            return;
        }

        // ยกเว้นเส้นทางที่ต้องเข้าถึงได้ (กัน redirect วน)
        // เทียบแบบ "segment" เพราะ URL อาจมี index.php นำหน้า
        $path = $request->getUri()->getPath();
        foreach (['logout', 'login', 'lang'] as $skip) {
            if (preg_match('#(^|/)' . preg_quote($skip, '#') . '(/|$)#', $path)) {
                return;
            }
        }

        // active = 0 → บัญชีถูกปิดใช้งาน → เตะออกจากระบบ + กลับไปหน้า login
        if ((int) (auth()->user()->active ?? 1) === 0) {
            auth()->logout();

            return redirect()->to(site_url('login'))->with('notice', lang('Common.accountDeactivated'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // ไม่ทำอะไร
    }
}
