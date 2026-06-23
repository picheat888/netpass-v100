<?php

namespace App\Controllers;

class Home extends BaseController
{
    // หน้าแรก — ส่งต่อตามสถานะ login/role
    public function index()
    {
        if (auth()->loggedIn()) {
            return redirect()->to(auth()->user()->inGroup('admin') ? '/admin' : '/myvoucher');
        }

        return redirect()->to('/login');
    }
}
