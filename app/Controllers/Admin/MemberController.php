<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\ActivityLog;
use CodeIgniter\Shield\Entities\User;

/**
 * จัดการสมาชิกในระบบ — เพิ่ม/แก้ไขโปรไฟล์/เปลี่ยนกลุ่ม/เปิด-ปิดการใช้งาน
 */
class MemberController extends BaseController
{
    // หน้า list (โครง) — ข้อมูลตารางโหลดผ่าน data()
    public function index()
    {
        return view('admin/members/index', [
            'title'    => lang('Member.title'),
            'subtitle' => lang('Member.subtitle'),
            'active'   => 'members',
        ]);
    }

    // base query ของสมาชิก (users + role) — ใช้ร่วม count/list
    // role คิดจาก "อยู่กลุ่ม admin ไหม" ผ่าน subquery → 1 user = 1 แถวเสมอ (กันรายชื่อซ้ำเมื่อ user อยู่หลายกลุ่ม)
    private function memberQuery()
    {
        return db_connect()->table('users u')
            ->select("u.id, u.username, u.firstname, u.lastname, u.position, u.img, u.active,
                      CASE WHEN EXISTS (SELECT 1 FROM auth_groups_users agx WHERE agx.user_id = u.id AND agx.`group` = 'admin')
                           THEN 'admin' ELSE 'user' END AS role,
                      ai.secret AS email", false)
            ->join('auth_identities ai', "ai.user_id = u.id AND ai.type = 'email_password'", 'left');
    }

    // ข้อมูลตาราง DataTables (server-side) → JSON
    public function data()
    {
        helper(['url', 'form']);
        $req    = $this->request;
        $draw   = (int) $req->getGet('draw');
        $start  = (int) $req->getGet('start');
        $length = (int) ($req->getGet('length') ?: 10);
        $search = trim((string) ($req->getGet('search')['value'] ?? ''));
        $group  = (string) $req->getGet('group');

        // ลำดับคอลัมน์: 0 ชื่อ-สกุล / 1 ตำแหน่ง / 2 email / 3 username / 4 role / 5 สถานะ
        $orderCols = [0 => 'u.firstname', 1 => 'u.position', 2 => 'ai.secret', 3 => 'u.username', 4 => 'role', 5 => 'u.active'];
        $orderIdx  = (int) ($req->getGet('order')[0]['column'] ?? 0);
        $orderDir  = strtolower((string) ($req->getGet('order')[0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $orderBy   = $orderCols[$orderIdx] ?? 'u.firstname';

        $recordsTotal = db_connect()->table('users')->countAllResults();

        $builder = $this->memberQuery();
        // กรองตามแท็บ role: admin = อยู่กลุ่ม admin / user = ไม่อยู่กลุ่ม admin (ตรงกับ role ใน memberQuery)
        if ($group === 'admin') {
            $builder->where("EXISTS (SELECT 1 FROM auth_groups_users agx WHERE agx.user_id = u.id AND agx.`group` = 'admin')", null, false);
        } elseif ($group === 'user') {
            $builder->where("NOT EXISTS (SELECT 1 FROM auth_groups_users agx WHERE agx.user_id = u.id AND agx.`group` = 'admin')", null, false);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('u.username', $search)->orLike('u.firstname', $search)->orLike('u.lastname', $search)
                ->groupEnd();
        }
        $recordsFiltered = $builder->countAllResults(false);

        $rows = $builder->orderBy($orderBy, $orderDir)->limit($length, $start)->get()->getResultArray();

        $currentId = (int) auth()->user()->id;
        $data = array_map(fn ($member) => $this->renderRow($member, $currentId), $rows);

        return $this->response->setJSON([
            'draw' => $draw, 'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered, 'data' => $data,
        ]);
    }

    // สร้าง HTML 7 คอลัมน์ (ชื่อ-สกุล / ตำแหน่ง / email / username / role / สถานะ / จัดการ)
    private function renderRow(array $member, int $currentId): array
    {
        $attr     = static fn ($value) => esc((string) $value, 'attr');
        $fullname = trim(($member['firstname'] ?? '') . ' ' . ($member['lastname'] ?? '')) ?: $member['username'];
        $base     = trim(($member['firstname'] ?? '') . ($member['lastname'] ?? '')) ?: ($member['username'] ?? 'U');
        $initial  = mb_strtoupper(mb_substr($base, 0, 1));
        $isSelf   = (int) $member['id'] === $currentId;
        $isAdmin  = $member['role'] === 'admin';

        $avatar = ! empty($member['img'])
            ? '<img src="' . $attr(base_url($member['img'])) . '" alt="">'
            : esc($initial);
        // คอลัมน์ชื่อ: รูป + ชื่อ-สกุล
        $c0 = '<div class="d-flex align-items-center gap-2">'
            . '<span class="np-avatar" style="width:36px;height:36px;font-size:13px">' . $avatar . '</span>'
            . '<div class="min-w-0"><div class="fw-semibold text-truncate" style="max-width:240px">' . esc($fullname) . '</div></div></div>';

        $c1 = $isAdmin
            ? '<span class="np-badge np-badge-blue">' . lang('Member.roleAdmin') . '</span>'
            : '<span class="np-badge np-badge-muted">' . lang('Member.roleUser') . '</span>';

        $c2 = '<span class="np-stat-sub">' . (trim((string) ($member['position'] ?? '')) !== '' ? esc($member['position']) : '—') . '</span>';

        // คอลัมน์ email — user ที่ไม่มีอีเมลจริง (ว่าง หรือ default @netpass.local) แสดง "—"
        $email   = trim((string) ($member['email'] ?? ''));
        $noEmail = $email === '' || str_ends_with(strtolower($email), '@netpass.local');
        $cEmail  = $noEmail ? '<span class="np-stat-sub">—</span>' : '<span>' . esc($email) . '</span>';

        // คอลัมน์ username
        $cUser = '<span class="font-mono">' . esc($member['username'] ?? '') . '</span>';

        // สถานะ: toggle switch (self = ปิดใช้ไม่ได้, ผู้อื่น = สลับผ่าน dialog ยืนยัน)
        if ($isSelf) {
            $c3 = '<div class="form-check form-switch m-0" title="' . $attr(lang('Member.cannotToggleSelf')) . '">'
                . '<input class="form-check-input" type="checkbox" role="switch" checked disabled></div>';
        } else {
            $c3 = '<div class="form-check form-switch m-0">'
                . '<input class="form-check-input mb-toggle" type="checkbox" role="switch"'
                . ' data-id="' . $attr($member['id']) . '" data-name="' . $attr($fullname) . '"'
                . ($member['active'] ? ' checked' : '') . '></div>';
        }

        $c4 = '<button class="np-icon-sm" title="' . $attr(lang('Common.edit')) . '" data-bs-toggle="modal" data-bs-target="#editModal"'
            . ' data-id="' . $attr($member['id']) . '" data-username="' . $attr($member['username'] ?? '') . '"'
            . ' data-email="' . $attr($member['email'] ?? '') . '" data-img="' . $attr(! empty($member['img']) ? base_url($member['img']) : '') . '"'
            . ' data-firstname="' . $attr($member['firstname'] ?? '') . '"'
            . ' data-lastname="' . $attr($member['lastname'] ?? '') . '" data-position="' . $attr($member['position'] ?? '') . '"'
            . ' data-initial="' . $attr($initial) . '" data-role="' . $attr($member['role']) . '"><i class="bi bi-pencil"></i></button>';

        // ปุ่ม reset รหัสผ่าน + ลบ — ทำกับบัญชีตัวเองไม่ได้ (ตัวเองใช้หน้าโปรไฟล์)
        if (! $isSelf) {
            $c4 .= '<button class="np-icon-sm np-warning ms-1" title="' . $attr(lang('Member.resetTitle')) . '" data-bs-toggle="modal" data-bs-target="#mbResetModal"'
                . ' data-id="' . $attr($member['id']) . '" data-name="' . $attr($fullname) . '" data-username="' . $attr($member['username'] ?? '') . '"><i class="bi bi-key"></i></button>';
            $c4 .= '<button class="np-icon-sm np-danger ms-1" title="' . $attr(lang('Common.delete')) . '" data-bs-toggle="modal" data-bs-target="#mbDeleteModal"'
                . ' data-id="' . $attr($member['id']) . '" data-name="' . $attr($fullname) . '"><i class="bi bi-trash"></i></button>';
        }

        // เรียงตามหัวตาราง: ชื่อ-สกุล / ตำแหน่ง / email / username / role / สถานะ / จัดการ
        return [$c0, $c2, $cEmail, $cUser, $c1, $c3, $c4];
    }

    // กฎ validation ฝั่ง server (ภาษาไทย/อังกฤษตาม locale) — โหมด create/edit ต่างกันที่ username+password
    private function rules(bool $isCreate): array
    {
        $rules = [
            // email ไม่บังคับ แต่ถ้ากรอกต้องถูก format
            'email' => [
                'rules'  => 'permit_empty|valid_email',
                'errors' => ['valid_email' => lang('Member.errEmailValid')],
            ],
            'firstname' => [
                'rules'  => 'required|max_length[150]',
                'errors' => ['required' => lang('Member.errFirstReq')],
            ],
            'lastname' => [
                'rules'  => 'required|max_length[150]',
                'errors' => ['required' => lang('Member.errLastReq')],
            ],
            'position' => [
                'rules'  => 'required|max_length[100]',
                'errors' => ['required' => lang('Member.errPositionReq')],
            ],
            // role ไม่บังคับ (ค่าเริ่มต้นเป็น user อยู่แล้ว)
            'role' => 'permit_empty|in_list[admin,user]',
        ];

        if ($isCreate) {
            $rules['username'] = [
                'rules'  => 'required|max_length[100]|is_unique[users.username]',
                'errors' => ['required' => lang('Member.errUsernameReq'), 'is_unique' => lang('Member.errUsernameTaken')],
            ];
            // รหัสผ่าน: ต้องมีพิมพ์ใหญ่+เล็ก+ตัวเลข+อักขระพิเศษ ยาว ≥ 8 (ตรงกับ checklist หน้าจอ)
            $rules['password'] = [
                'rules'  => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/]',
                'errors' => ['required' => lang('Member.errPwdReq'), 'min_length' => lang('Member.errPwdMin'), 'regex_match' => lang('Member.errPwdWeak')],
            ];
        }

        return $rules;
    }

    // อัปโหลด/ย่อรูปโปรไฟล์ของสมาชิก (jpg/png → 256×256) แล้วคืน path ที่เก็บ (หรือ null ถ้าไม่มีไฟล์)
    private function handleAvatar(int $userId): ?string
    {
        $avatar = $this->request->getFile('avatar');
        if (! ($avatar && $avatar->isValid() && ! $avatar->hasMoved())) {
            return null;
        }

        $dir = FCPATH . 'uploads/avatars/';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $newName = 'avatar_' . $userId . '_' . time() . '.' . $avatar->getExtension();
        $avatar->move($dir, $newName);
        $fullPath = $dir . $newName;

        // ย่อ/ครอปกลางภาพเป็น 256×256 (ถ้าเซิร์ฟเวอร์มี GD)
        if (extension_loaded('gd')) {
            try {
                service('image')->withFile($fullPath)->fit(256, 256, 'center')->save($fullPath);
            } catch (\Throwable $e) {
                // ย่อไม่ได้ก็ใช้รูปเดิม
            }
        }

        return 'uploads/avatars/' . $newName;
    }

    // สร้าง user ใหม่ผ่าน Shield (identity email_password + group + force_reset) — ใช้ร่วม create() และ import()
    private function createMember(array $data, string $role, bool $forceReset): int
    {
        $users   = auth()->getProvider();
        $newUser = new User([
            'username'  => $data['username'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'firstname' => $data['firstname'],
            'lastname'  => $data['lastname'],
            'position'  => $data['position'],
            'active'    => 1,
        ]);
        $users->save($newUser);

        $id = $users->getInsertID();
        $users->findById($id)->addGroup($role);

        if ($forceReset) {
            db_connect()->table('auth_identities')
                ->where('user_id', $id)->where('type', 'email_password')
                ->update(['force_reset' => 1]);
        }

        return $id;
    }

    // สร้างสมาชิกใหม่ผ่าน Shield UserProvider (email+password = identity, role = group, avatar = ตัวเลือก)
    public function create()
    {
        $rules = $this->rules(true);

        // ตรวจไฟล์รูปเฉพาะเมื่อมีการเลือกมา
        $avatar    = $this->request->getFile('avatar');
        $hasAvatar = $avatar && $avatar->isValid() && ! $avatar->hasMoved();
        if ($hasAvatar) {
            $rules['avatar'] = [
                'rules'  => 'is_image[avatar]|mime_in[avatar,image/jpg,image/jpeg,image/png]|ext_in[avatar,jpg,jpeg,png]|max_size[avatar,4096]',
                'errors' => ['is_image' => lang('Profile.avatarBadType'), 'mime_in' => lang('Profile.avatarBadType'), 'ext_in' => lang('Profile.avatarBadType'), 'max_size' => lang('Profile.avatarTooBig')],
            ];
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('mb_errors', $this->validator->getErrors())->with('mb_form', 'add');
        }

        // อีเมล: ถ้าไม่กรอก ใช้ค่า username@netpass.local (Shield ต้องมี email เพื่อสร้าง identity ให้ login ได้)
        $username = (string) $this->request->getPost('username');
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        if ($email === '') {
            $email = strtolower($username) . '@netpass.local';
        }
        // อีเมลต้องไม่ซ้ำ (เก็บใน auth_identities type email_password)
        $taken = db_connect()->table('auth_identities')
            ->where('type', 'email_password')->where('secret', $email)->countAllResults();
        if ($taken > 0) {
            return redirect()->back()->withInput()->with('mb_errors', ['email' => lang('Member.errEmailTaken')])->with('mb_form', 'add');
        }

        // role ไม่บังคับ → ค่าเริ่มต้น user
        $role = $this->request->getPost('role') ?: 'user';

        // สร้าง user ผ่าน helper (ใช้ logic เดียวกับ import)
        $id = $this->createMember([
            'username'  => $username,
            'email'     => $email,
            'password'  => $this->request->getPost('password'),
            'firstname' => $this->request->getPost('firstname'),
            'lastname'  => $this->request->getPost('lastname'),
            'position'  => $this->request->getPost('position'),
        ], $role, (bool) $this->request->getPost('force_change'));

        // รูปโปรไฟล์ (ตัวเลือก)
        $img = $this->handleAvatar($id);
        if ($img !== null) {
            (new UserModel())->update($id, ['img' => $img]);
        }

        $fullName = trim((string) $this->request->getPost('firstname') . ' ' . (string) $this->request->getPost('lastname'));
        ActivityLog::record('member.create', [
            'target_type'  => 'member',
            'target_id'    => $id,
            'target_label' => $fullName ?: $username,
            'details'      => [
                'name'     => $fullName,
                'username' => $username,
                'email'    => $email,
                'position' => $this->request->getPost('position'),
                'role'     => $role,
            ],
        ]);

        return redirect()->to(site_url('admin/members'))->with('message', lang('Member.created'));
    }

    // แก้ไขโปรไฟล์และกลุ่มของสมาชิก
    public function update(int $id)
    {
        $userModel = new UserModel();
        $member    = $userModel->find($id);
        if (! $member) {
            return redirect()->to(site_url('admin/members'))->with('error', lang('Member.notFound'));
        }

        $rules = $this->rules(false);

        // ตรวจไฟล์รูปเฉพาะเมื่อมีการเลือกมา
        $avatar    = $this->request->getFile('avatar');
        $hasAvatar = $avatar && $avatar->isValid() && ! $avatar->hasMoved();
        if ($hasAvatar) {
            $rules['avatar'] = [
                'rules'  => 'is_image[avatar]|mime_in[avatar,image/jpg,image/jpeg,image/png]|ext_in[avatar,jpg,jpeg,png]|max_size[avatar,4096]',
                'errors' => ['is_image' => lang('Profile.avatarBadType'), 'mime_in' => lang('Profile.avatarBadType'), 'ext_in' => lang('Profile.avatarBadType'), 'max_size' => lang('Profile.avatarTooBig')],
            ];
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('mb_errors', $this->validator->getErrors())
                ->with('mb_form', 'edit')->with('mb_edit_id', $id);
        }

        // อีเมล (ถ้ากรอก) ต้องไม่ซ้ำกับผู้ใช้อื่น — ถ้าเว้นว่างจะไม่แตะ identity เดิม
        $email = strtolower(trim((string) $this->request->getPost('email')));
        if ($email !== '') {
            $taken = db_connect()->table('auth_identities')
                ->where('type', 'email_password')->where('secret', $email)->where('user_id !=', $id)->countAllResults();
            if ($taken > 0) {
                return redirect()->back()->withInput()
                    ->with('mb_errors', ['email' => lang('Member.errEmailTaken')])
                    ->with('mb_form', 'edit')->with('mb_edit_id', $id);
            }
        }

        // อัปเดตโปรไฟล์ (+ รูปถ้ามีอัปโหลดใหม่)
        $data = [
            'firstname' => $this->request->getPost('firstname'),
            'lastname'  => $this->request->getPost('lastname'),
            'position'  => $this->request->getPost('position'),
        ];
        $img = $this->handleAvatar($id);
        if ($img !== null) {
            // ลบรูปเก่ากันไฟล์ค้าง
            $old = $member->img ?? null;
            if ($old && is_file(FCPATH . $old)) {
                @unlink(FCPATH . $old);
            }
            $data['img'] = $img;
        }
        $userModel->update($id, $data);

        // อัปเดตอีเมลใน identity (เฉพาะเมื่อกรอกมา)
        if ($email !== '') {
            db_connect()->table('auth_identities')
                ->where('user_id', $id)->where('type', 'email_password')
                ->update(['secret' => $email]);
        }

        // เปลี่ยนกลุ่ม — ลบกลุ่มเดิมออก แล้วใส่กลุ่มใหม่ (role ไม่บังคับ → default user)
        $role       = $this->request->getPost('role') ?: 'user';
        $oldRole    = null;
        $shieldUser = auth()->getProvider()->findById($id);
        if ($shieldUser) {
            $currentGroups = $shieldUser->getGroups();
            $oldRole       = $currentGroups[0] ?? null;
            foreach ($currentGroups as $group) {
                $shieldUser->removeGroup($group);
            }
            $shieldUser->addGroup($role);
        }

        ActivityLog::record('member.update', [
            'target_type'  => 'member',
            'target_id'    => $id,
            'target_label' => trim((string) $data['firstname'] . ' ' . (string) $data['lastname']) ?: ($member->username ?? ''),
            'details'      => [
                'before' => [
                    'name'     => trim((string) ($member->firstname ?? '') . ' ' . (string) ($member->lastname ?? '')),
                    'position' => $member->position ?? '',
                    'role'     => $oldRole,
                ],
                'after' => [
                    'name'     => trim((string) $data['firstname'] . ' ' . (string) $data['lastname']),
                    'position' => $data['position'],
                    'role'     => $role,
                ],
                'email_changed' => $email !== '',
            ],
        ]);

        return redirect()->to(site_url('admin/members'))->with('message', lang('Member.updated'));
    }

    // ลบสมาชิก (ลบบัญชีตัวเองไม่ได้) — ลบ identity/group/log + รูป แล้วลบ user
    public function delete(int $id)
    {
        if ($id === (int) auth()->id()) {
            return redirect()->to(site_url('admin/members'))->with('error', lang('Member.cannotDeleteSelf'));
        }

        $userModel = new UserModel();
        $member    = $userModel->find($id);   // คืนเป็น Shield User entity (เข้าถึงด้วย property)
        if (! $member) {
            return redirect()->to(site_url('admin/members'))->with('error', lang('Member.notFound'));
        }

        $db = db_connect();
        $db->table('auth_identities')->where('user_id', $id)->delete();
        $db->table('auth_groups_users')->where('user_id', $id)->delete();
        $db->table('auth_logins')->where('user_id', $id)->delete();
        $db->table('auth_token_logins')->where('user_id', $id)->delete();
        $db->table('auth_remember_tokens')->where('user_id', $id)->delete();

        // ลบไฟล์รูปโปรไฟล์ (ถ้ามี)
        $img = $member->img ?? null;
        if (! empty($img) && is_file(FCPATH . $img)) {
            @unlink(FCPATH . $img);
        }

        $delName = trim((string) ($member->firstname ?? '') . ' ' . (string) ($member->lastname ?? ''));
        $userModel->delete($id, true);   // ลบจริง (purge) ให้ username/email ว่างไว้ใช้ใหม่ได้

        ActivityLog::record('member.delete', [
            'target_type'  => 'member',
            'target_id'    => $id,
            'target_label' => $delName ?: ($member->username ?? ''),
            'details'      => ['name' => $delName, 'username' => $member->username ?? ''],
        ]);

        return redirect()->to(site_url('admin/members'))->with('message_danger', lang('Member.deleted'));
    }

    // reset รหัสผ่านให้สมาชิก (admin ตั้งรหัสใหม่) + บังคับเปลี่ยนครั้งแรกได้
    public function resetPassword(int $id)
    {
        $member = (new UserModel())->find($id);
        if (! $member) {
            return redirect()->to(site_url('admin/members'))->with('error', lang('Member.notFound'));
        }

        $rules = [
            'new_password' => [
                'rules'  => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/]',
                'errors' => ['required' => lang('Member.errPwdReq'), 'min_length' => lang('Member.errPwdMin'), 'regex_match' => lang('Member.errPwdWeak')],
            ],
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('mb_errors', $this->validator->getErrors())
                ->with('mb_form', 'reset')->with('mb_reset_id', $id);
        }

        // ตั้งรหัสใหม่ผ่าน Shield
        $users = auth()->getProvider();
        $user  = $users->findById($id);
        $users->save($user->fill(['password' => $this->request->getPost('new_password')]));

        // บังคับเปลี่ยนรหัสตอน login ครั้งถัดไป (ถ้าเลือก)
        $force = $this->request->getPost('force_change') ? 1 : 0;
        db_connect()->table('auth_identities')
            ->where('user_id', $id)->where('type', 'email_password')
            ->update(['force_reset' => $force]);

        ActivityLog::record('member.reset_password', [
            'target_type'  => 'member',
            'target_id'    => $id,
            'target_label' => trim((string) ($member->firstname ?? '') . ' ' . (string) ($member->lastname ?? '')) ?: ($member->username ?? ''),
            'details'      => ['force_change' => (bool) $force],
        ]);

        return redirect()->to(site_url('admin/members'))->with('message', lang('Member.resetDone'));
    }

    // เปิด/ปิดการใช้งานบัญชี (ป้องกันปิดบัญชีตัวเอง)
    public function toggle(int $id)
    {
        if ($id === (int) auth()->user()->id) {
            return redirect()->to(site_url('admin/members'))->with('error', lang('Member.cannotToggleSelf'));
        }

        $userModel = new UserModel();
        $member    = $userModel->find($id);
        if (! $member) {
            return redirect()->to(site_url('admin/members'))->with('error', lang('Member.notFound'));
        }

        // $member เป็น Shield User entity → เข้าถึงด้วย property
        $isActive = (int) $member->active === 1;
        $userModel->update($id, ['active' => $isActive ? 0 : 1]);

        ActivityLog::record('member.toggle', [
            'target_type'  => 'member',
            'target_id'    => $id,
            'target_label' => trim((string) ($member->firstname ?? '') . ' ' . (string) ($member->lastname ?? '')) ?: ($member->username ?? ''),
            'details'      => ['from' => $isActive ? 'active' : 'inactive', 'to' => $isActive ? 'inactive' : 'active'],
        ]);

        // ปิดใช้งาน = toast แดง (เชิงลบ), เปิดใช้งาน = toast เขียว (ปกติ)
        if ($isActive) {
            return redirect()->to(site_url('admin/members'))->with('message_danger', lang('Member.deactivated'));
        }

        return redirect()->to(site_url('admin/members'))->with('message', lang('Member.activated'));
    }

    // สร้างไฟล์ .csv template (header + แถวตัวอย่าง) ให้ดาวน์โหลด — มี UTF-8 BOM ให้ Excel อ่านถูก
    // ค่าตัวอย่างผ่าน csv_safe() ทุก cell เพื่อกัน formula injection (template ปลอดภัย)
    public function importTemplate()
    {
        $bom = "\xEF\xBB\xBF";

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Firstname', 'lastname', 'email', 'position', 'username', 'password']);
        fputcsv($out, array_map('csv_safe', ['John', 'Doe', 'john@example.com', 'Staff', 'jdoe', 'pass1234']));
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="member_import_template.csv"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($bom . $csv);
    }

    // นำเข้าสมาชิกจากไฟล์ csv/xlsx — ตรวจทีละแถว, สร้างแถวที่ผ่าน, คืน JSON สรุป (% สำเร็จ + แถวพลาด)
    public function import()
    {
        // helper คืน JSON error พร้อม csrf hash ใหม่ (กัน token หมดอายุ)
        $fail = fn (string $msg) => $this->response->setStatusCode(400)
            ->setJSON(['error' => $msg, 'csrf' => csrf_hash()]);

        // ── ตรวจไฟล์ ──
        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid()) {
            return $fail(lang('Member.importNoFile'));
        }
        $ext    = strtolower($file->getClientExtension());
        $mime   = $file->getMimeType();   // ตรวจฝั่ง server (finfo) — กัน rename ไฟล์อื่นเป็น .csv
        $okMime = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
        if ($ext !== 'csv' || ! in_array($mime, $okMime, true) || $file->getSize() > 2 * 1024 * 1024) {
            return $fail(lang('Member.importBadFile'));
        }

        // ── อ่านแถวด้วย fgetcsv (PHP ในตัว — ไม่ต้องใช้ ext-zip) ──
        $rows   = [];
        $handle = fopen($file->getTempName(), 'r');
        if ($handle === false) {
            return $fail(lang('Member.importBadFile'));
        }
        while (($cells = fgetcsv($handle)) !== false) {
            $rows[] = $cells;
        }
        fclose($handle);
        // ตัด UTF-8 BOM ที่อาจติดมากับเซลล์แรก (Excel ใส่มาให้)
        if (isset($rows[0][0])) {
            $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $rows[0][0]);
        }
        array_shift($rows);   // ข้าม header (แถวแรกเสมอ)
        // ตัดแถวว่างทั้งแถว
        $rows = array_values(array_filter($rows, static fn ($r) => trim(implode('', array_map('strval', (array) $r))) !== ''));
        if (count($rows) === 0) {
            return $fail(lang('Member.importEmptyFile'));
        }
        if (count($rows) > 1000) {
            return $fail(lang('Member.importBadFile'));
        }

        // ── วนตรวจ + สร้าง ──
        $db        = db_connect();
        $seenUser  = [];   // กันชื่อผู้ใช้ซ้ำในไฟล์
        $seenEmail = [];   // กันอีเมลซ้ำในไฟล์
        $success   = 0;
        $failures  = [];

        foreach ($rows as $idx => $r) {
            $rowNo     = $idx + 2;   // +2: header(1) + index 0-based
            $firstname = trim((string) ($r[0] ?? ''));
            $lastname  = trim((string) ($r[1] ?? ''));
            $email     = strtolower(trim((string) ($r[2] ?? '')));
            $position  = trim((string) ($r[3] ?? ''));
            $username  = trim((string) ($r[4] ?? ''));
            $password  = (string) ($r[5] ?? '');

            $reject = static function (string $reason) use (&$failures, $rowNo, $username) {
                $failures[] = ['row' => $rowNo, 'username' => $username, 'reason' => $reason];
            };

            // required
            if ($firstname === '') { $reject(lang('Member.importErrFirst'));    continue; }
            if ($lastname === '')  { $reject(lang('Member.importErrLast'));     continue; }
            if ($position === '')  { $reject(lang('Member.importErrPosition')); continue; }
            if ($username === '')  { $reject(lang('Member.importErrUserReq'));  continue; }

            // username ไม่ซ้ำ (ในไฟล์ + DB)
            if (isset($seenUser[strtolower($username)])) { $reject(lang('Member.importErrUserDup')); continue; }
            if ($db->table('users')->where('username', $username)->countAllResults() > 0) {
                $reject(lang('Member.importErrUserTaken'));
                continue;
            }

            // password: ต้องไม่ว่าง และต้องเข้มเท่ากฎตอนสร้างปกติ (≥8 + upper+lower+digit+special)
            if ($password === '') { $reject(lang('Member.importErrPwdReq')); continue; }
            if (! preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
                $reject(lang('Member.errPwdWeak'));
                continue;
            }

            // email (ว่าง → default username@netpass.local)
            if ($email === '') {
                $email = strtolower($username) . '@netpass.local';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $reject(lang('Member.importErrEmailValid'));
                continue;
            }
            if (isset($seenEmail[$email]) ||
                $db->table('auth_identities')->where('type', 'email_password')->where('secret', $email)->countAllResults() > 0) {
                $reject(lang('Member.importErrEmailTaken'));
                continue;
            }

            // สร้าง — role=user, force_reset=true เสมอ
            try {
                $this->createMember([
                    'username'  => $username,
                    'email'     => $email,
                    'password'  => $password,
                    'firstname' => $firstname,
                    'lastname'  => $lastname,
                    'position'  => $position,
                ], 'user', true);
                $seenUser[strtolower($username)] = true;
                $seenEmail[$email]               = true;
                $success++;
            } catch (\Throwable $e) {
                $reject(lang('Member.importErrCreate'));
            }
        }

        $total   = $success + count($failures);
        $percent = $total > 0 ? (int) round($success / $total * 100) : 0;

        return $this->response->setJSON([
            'total'    => $total,
            'success'  => $success,
            'failed'   => count($failures),
            'percent'  => $percent,
            'failures' => $failures,
            'csrf'     => csrf_hash(),
        ]);
    }
}
