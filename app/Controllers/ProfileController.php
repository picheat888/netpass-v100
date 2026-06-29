<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\ActivityLog;

/**
 * จัดการโปรไฟล์ส่วนตัว — แก้ไขชื่อ/ตำแหน่ง และเปลี่ยนรหัสผ่าน (ใช้ร่วมกันทั้ง admin และ user)
 */
class ProfileController extends BaseController
{
    // แสดงหน้าโปรไฟล์ พร้อมข้อมูลปัจจุบัน
    public function index()
    {
        $user      = auth()->user();
        $isAdmin   = $user->inGroup('admin');
        $userModel = new UserModel();
        $profile   = $userModel->find($user->id);

        $viewPath = $isAdmin ? 'admin/profile/index' : 'user/profile/index';

        return view($viewPath, [
            'title'    => lang('Profile.title'),
            'active'   => 'profile',
            'profile'  => $profile,
            'user'     => $user,
            // URL base ของ form (ส่งให้ profile_content ที่ include ภายใน view)
            'formBase' => $isAdmin ? 'admin/profile' : 'profile',
        ]);
    }

    // บันทึกการแก้ไขชื่อ/ตำแหน่ง + รูปโปรไฟล์ (อัปโหลด jpg/png แล้วย่อขนาด)
    public function update()
    {
        // ชื่อ/นามสกุล/อีเมล แก้ได้เฉพาะ admin — ฝั่ง user ช่องถูก disable (admin เป็นผู้กำหนด)
        $isAdmin = auth()->user()->inGroup('admin');

        $rules    = [];
        $messages = [];

        // กฎเฉพาะ admin: ชื่อ/นามสกุล/อีเมล บังคับกรอก (position ไม่ตรวจ — admin กำหนดผ่านหน้า Member)
        if ($isAdmin) {
            $rules = [
                'firstname' => 'required|max_length[150]',
                'lastname'  => 'required|max_length[150]',
                'email'     => 'required|valid_email',
            ];
            $messages = [
                'firstname' => [
                    'required'   => lang('Profile.errFirstRequired'),
                    'max_length' => lang('Profile.errMaxLen'),
                ],
                'lastname' => [
                    'required'   => lang('Profile.errLastRequired'),
                    'max_length' => lang('Profile.errMaxLen'),
                ],
                'email' => [
                    'required'    => lang('Profile.errEmailRequired'),
                    'valid_email' => lang('Profile.errEmailValid'),
                ],
            ];
        }

        // ตรวจไฟล์รูปเฉพาะเมื่อมีการเลือกไฟล์มา — รับเฉพาะ jpg/png และไม่เกิน 4MB
        $avatar    = $this->request->getFile('avatar');
        $hasAvatar = $avatar && $avatar->isValid() && ! $avatar->hasMoved();
        if ($hasAvatar) {
            $rules['avatar']    = 'is_image[avatar]|mime_in[avatar,image/jpg,image/jpeg,image/png]|ext_in[avatar,jpg,jpeg,png]|max_size[avatar,4096]';
            $messages['avatar'] = [
                'is_image' => lang('Profile.avatarBadType'),
                'mime_in'  => lang('Profile.avatarBadType'),
                'ext_in'   => lang('Profile.avatarBadType'),
                'max_size' => lang('Profile.avatarTooBig'),
            ];
        }

        if (! $this->validate($rules, $messages)) {
            // ใช้ key เฉพาะ (prof_errors) เพื่อแสดง error แบบ inline ใต้ช่อง — ไม่ให้กล่อง alert ของ layout เด้งซ้ำ
            return redirect()->back()->withInput()->with('prof_errors', $this->validator->getErrors());
        }

        $userId    = auth()->user()->id;
        $userModel = new UserModel();
        $db        = db_connect();

        // ไม่บันทึก position จากหน้านี้ — เป็นค่าที่ admin กำหนดให้ (ช่องถูก disable ไว้)
        $data  = [];
        $email = null;

        // ชื่อ/นามสกุล/อีเมล อัปเดตได้เฉพาะ admin — ฝั่ง user ช่องถูก disable จึงข้าม (กัน tamper จาก client)
        if ($isAdmin) {
            // อีเมล: เก็บใน auth_identities (type email_password) — เช็คไม่ให้ซ้ำกับผู้ใช้อื่นก่อน
            $email = strtolower(trim((string) $this->request->getPost('email')));
            $taken = $db->table('auth_identities')
                ->where('type', 'email_password')
                ->where('secret', $email)
                ->where('user_id !=', $userId)
                ->countAllResults();
            if ($taken > 0) {
                return redirect()->back()->withInput()->with('prof_errors', ['email' => lang('Profile.errEmailTaken')]);
            }

            $data['firstname'] = $this->request->getPost('firstname');
            $data['lastname']  = $this->request->getPost('lastname');
        }

        // จัดการรูปโปรไฟล์
        if ($hasAvatar) {
            $dir = FCPATH . 'uploads/avatars/';
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            $newName = 'avatar_' . $userId . '_' . time() . '.' . $avatar->getExtension();
            $avatar->move($dir, $newName);
            $fullPath = $dir . $newName;

            // ย่อขนาด/ครอปกลางภาพเป็น 256×256 (ถ้าเซิร์ฟเวอร์มี GD; ฝั่ง browser ย่อมาแล้วชั้นหนึ่ง)
            if (extension_loaded('gd')) {
                try {
                    service('image')->withFile($fullPath)->fit(256, 256, 'center')->save($fullPath);
                } catch (\Throwable $e) {
                    // ถ้าย่อไม่สำเร็จก็ใช้รูปที่อัปโหลดมาตามเดิม
                }
            }

            // ลบรูปเก่า (ถ้ามี) เพื่อไม่ให้ไฟล์ค้าง
            $old = $userModel->find($userId)->img ?? null;
            if ($old && is_file(FCPATH . $old)) {
                @unlink(FCPATH . $old);
            }

            $data['img'] = 'uploads/avatars/' . $newName;
        }

        // บันทึกเฉพาะเมื่อมีข้อมูลให้อัปเดต (user ที่ไม่ได้เปลี่ยนรูป จะไม่มีอะไรเปลี่ยน)
        if (! empty($data)) {
            $userModel->update($userId, $data);
        }

        // อัปเดตอีเมลใน identity ของ Shield (email_password) — เฉพาะ admin
        if ($isAdmin && $email !== null) {
            $db->table('auth_identities')
                ->where('user_id', $userId)
                ->where('type', 'email_password')
                ->update(['secret' => $email]);
        }

        ActivityLog::record('profile.update', [
            'target_type'  => 'member',
            'target_id'    => $userId,
            'target_label' => ActivityLog::displayName(auth()->user()),
            'details'      => [
                'fields'        => array_keys($data),
                'email_changed' => $isAdmin && $email !== null,
            ],
        ]);

        return redirect()->back()->with('message', lang('Profile.updated'));
    }

    // เปลี่ยนรหัสผ่าน — ตรวจสอบรหัสเดิมก่อนบันทึกรหัสใหม่
    public function changePassword()
    {
        // รหัสใหม่ต้องมี พิมพ์ใหญ่ + พิมพ์เล็ก + ตัวเลข + อักขระพิเศษ + ยาว ≥ 8 (ตรงกับ checklist หน้าจอ)
        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/]',
            'confirm_password' => 'required|matches[new_password]',
        ];
        $messages = [
            'current_password' => ['required' => lang('Profile.errCurrentRequired')],
            'new_password'     => [
                'required'     => lang('Profile.errNewRequired'),
                'min_length'   => lang('Profile.minLength'),
                'regex_match'  => lang('Profile.errPwdWeak'),
            ],
            'confirm_password' => [
                'required' => lang('Profile.errConfirmRequired'),
                'matches'  => lang('Profile.errConfirmMatch'),
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            // คงค่าที่กรอกไว้ (รหัสใหม่/ยืนยัน) ไม่ให้หายเมื่อ validation ไม่ผ่าน
            return redirect()->back()->withInput()->with('pwd_errors', $this->validator->getErrors());
        }

        $user    = auth()->user();
        $current = $this->request->getPost('current_password');
        $new     = $this->request->getPost('new_password');

        // ดึง hash ปัจจุบันจาก auth_identities
        $identity = db_connect()
            ->table('auth_identities')
            ->where('user_id', $user->id)
            ->where('type', 'email_password')
            ->get()->getRowArray();

        if (! $identity || ! service('passwords')->verify($current, $identity['secret2'])) {
            // รหัสปัจจุบันผิด — คงรหัสใหม่ที่พิมพ์ไว้ ให้ผู้ใช้แก้เฉพาะช่องรหัสปัจจุบัน
            return redirect()->back()->withInput()->with('pwd_errors', ['current_password' => lang('Profile.wrongPassword')]);
        }

        // อัปเดตรหัสผ่านใหม่ผ่าน Shield
        $users = auth()->getProvider();
        $users->save($user->fill(['password' => $new]));

        ActivityLog::record('auth.password_change', [
            'target_type'  => 'member',
            'target_id'    => (int) $user->id,
            'target_label' => ActivityLog::displayName($user),
            'details'      => ['via' => 'profile'],
        ]);

        return redirect()->back()->with('pwd_message', lang('Profile.passwordChanged'));
    }
}
