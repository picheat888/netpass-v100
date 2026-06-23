<?php

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

/**
 * UserModel — ต่อยอดจาก Shield เพิ่ม profile fields (firstname/lastname/position/img)
 */
class UserModel extends ShieldUserModel
{
    protected function initialize(): void
    {
        parent::initialize();

        // เพิ่มคอลัมน์โปรไฟล์ที่ยอมให้บันทึก
        $this->allowedFields = array_merge($this->allowedFields, [
            'firstname',
            'lastname',
            'position',
            'img',
            'locale',
        ]);
    }
}
