<?php

namespace App\Controllers;

use App\Services\ActivityLog;

/**
 * บังคับเปลี่ยนรหัสผ่านเมื่อ login ครั้งแรก (identity มี force_reset = 1)
 * ผู้ใช้ตั้งรหัสใหม่เองก่อนเข้าใช้งานระบบส่วนอื่น
 */
class ForcePasswordController extends BaseController
{
    // ตรวจว่าผู้ใช้ปัจจุบันถูกบังคับเปลี่ยนรหัสอยู่หรือไม่
    private function mustChange(): bool
    {
        $row = db_connect()->table('auth_identities')
            ->where('user_id', auth()->id())
            ->where('type', 'email_password')
            ->get()->getRowArray();

        return $row && (int) $row['force_reset'] === 1;
    }

    // ปลายทางหลังเปลี่ยนรหัสเสร็จ — แยกตามสิทธิ์
    private function home(): string
    {
        return auth()->user()->inGroup('admin') ? site_url('admin') : site_url('myvoucher');
    }

    // แสดงหน้าตั้งรหัสผ่านใหม่
    public function index()
    {
        if (! $this->mustChange()) {
            return redirect()->to($this->home());
        }

        return view('auth/force_password', [
            'title' => lang('Force.title'),
        ]);
    }

    // บันทึกรหัสผ่านใหม่ แล้วล้าง force_reset
    public function update()
    {
        if (! $this->mustChange()) {
            return redirect()->to($this->home());
        }

        // รหัสใหม่ต้องแข็งแรง (พิมพ์ใหญ่+เล็ก+ตัวเลข+อักขระพิเศษ ≥ 8) และยืนยันให้ตรง
        $rules = [
            'new_password' => [
                'rules'  => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/]',
                'errors' => [
                    'required'    => lang('Force.errNewRequired'),
                    'min_length'  => lang('Force.errMin'),
                    'regex_match' => lang('Force.errWeak'),
                ],
            ],
            'confirm_password' => [
                'rules'  => 'required|matches[new_password]',
                'errors' => [
                    'required' => lang('Force.errConfirmRequired'),
                    'matches'  => lang('Force.errConfirmMatch'),
                ],
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('force_errors', $this->validator->getErrors());
        }

        $new   = $this->request->getPost('new_password');
        $user  = auth()->user();
        $users = auth()->getProvider();

        // อัปเดตรหัสผ่านผ่าน Shield แล้วปลด force_reset
        $users->save($user->fill(['password' => $new]));
        db_connect()->table('auth_identities')
            ->where('user_id', auth()->id())->where('type', 'email_password')
            ->update(['force_reset' => 0]);

        ActivityLog::record('auth.password_change', [
            'target_type'  => 'member',
            'target_id'    => (int) auth()->id(),
            'target_label' => ActivityLog::displayName($user),
            'details'      => ['via' => 'force_reset'],
        ]);

        return redirect()->to($this->home())->with('message', lang('Force.changed'));
    }
}
