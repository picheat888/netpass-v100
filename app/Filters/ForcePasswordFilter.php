<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * บังคับเปลี่ยนรหัสผ่านตอน login ครั้งแรก —
 * ถ้า identity ของผู้ใช้มี force_reset = 1 ให้ส่งไปหน้าเปลี่ยนรหัสก่อนใช้งานส่วนอื่น
 */
class ForcePasswordFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! auth()->loggedIn()) {
            return;
        }

        // ยกเว้นเส้นทางที่ต้องเข้าถึงได้ระหว่างบังคับเปลี่ยนรหัส (กัน redirect วน)
        // เทียบแบบ "segment" เพราะ URL อาจมี index.php นำหน้า (เช่น index.php/force-password)
        $path = $request->getUri()->getPath();
        foreach (['force-password', 'logout', 'login', 'lang'] as $skip) {
            if (preg_match('#(^|/)' . preg_quote($skip, '#') . '(/|$)#', $path)) {
                return;
            }
        }

        $row = db_connect()->table('auth_identities')
            ->where('user_id', auth()->id())
            ->where('type', 'email_password')
            ->get()->getRowArray();

        if ($row && (int) $row['force_reset'] === 1) {
            return redirect()->to(site_url('force-password'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // ไม่ทำอะไร
    }
}
