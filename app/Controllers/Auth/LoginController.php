<?php

namespace App\Controllers\Auth;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Controllers\LoginController as ShieldLoginController;

/**
 * LoginController — override Shield ให้ login ด้วย username แทน email
 */
class LoginController extends ShieldLoginController
{
    // กฎ validate ฟอร์ม login (ใช้ username + password)
    protected function getValidationRules(): array
    {
        return [
            'username' => [
                'label' => lang('Common.username'),
                'rules' => 'required|string',
                'errors' => ['required' => lang('Common.enterUsername')],
            ],
            'password' => [
                'label' => lang('Common.password'),
                'rules' => 'required',
                'errors' => ['required' => lang('Common.enterPassword')],
            ],
        ];
    }

    // logout แล้ว redirect กลับหน้า login โดยไม่ตั้ง flash "successfully logged out" (ตัด noise ที่ไม่จำเป็น)
    public function logoutAction(): RedirectResponse
    {
        $url = config('Auth')->logoutRedirect();

        auth()->logout();

        return redirect()->to($url);
    }
}
