<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * สลับภาษา — เก็บ locale ลง session และบันทึกลงโปรไฟล์ผู้ใช้ (ถ้า login อยู่)
 */
class LanguageController extends BaseController
{
    public function switch(string $locale)
    {
        if (in_array($locale, config('App')->supportedLocales, true)) {
            session()->set('locale', $locale);

            // ถ้า login อยู่ ให้จำภาษาที่เลือกไว้กับ user คนนั้น (ติดตัวข้าม session/อุปกรณ์)
            if (auth()->loggedIn()) {
                (new UserModel())->update(auth()->id(), ['locale' => $locale]);
            }
        }

        return redirect()->back();
    }
}
