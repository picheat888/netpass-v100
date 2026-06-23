<?php

/**
 * แปลข้อความของ CodeIgniter Shield เป็นภาษาไทย
 * (override ไฟล์ vendor/codeigniter4/shield/src/Language/en/Auth.php)
 */
return [
    // Exceptions
    'unknownAuthenticator'  => '{0} ไม่ใช่ authenticator ที่ถูกต้อง',
    'unknownUserProvider'   => 'ไม่สามารถระบุ User Provider ที่จะใช้ได้',
    'invalidUser'           => 'ไม่พบผู้ใช้ที่ระบุ',
    'bannedUser'            => 'ไม่สามารถเข้าสู่ระบบได้ เนื่องจากบัญชีถูกระงับ',
    'logOutBannedUser'      => 'คุณถูกออกจากระบบเนื่องจากบัญชีถูกระงับ',
    'badAttempt'            => 'เข้าสู่ระบบไม่สำเร็จ กรุณาตรวจสอบชื่อผู้ใช้และรหัสผ่าน',
    'noPassword'            => 'ไม่สามารถตรวจสอบผู้ใช้ที่ไม่มีรหัสผ่านได้',
    'invalidPassword'       => 'เข้าสู่ระบบไม่สำเร็จ รหัสผ่านไม่ถูกต้อง',
    'noToken'               => 'ทุก request ต้องมี bearer token ใน header {0}',
    'badToken'              => 'access token ไม่ถูกต้อง',
    'oldToken'              => 'access token หมดอายุแล้ว',
    'noUserEntity'          => 'ต้องระบุ User Entity เพื่อตรวจสอบรหัสผ่าน',
    'invalidEmail'          => 'ไม่สามารถยืนยันได้ว่าอีเมล "{0}" ตรงกับที่บันทึกไว้',
    'unableSendEmailToUser' => 'ขออภัย เกิดปัญหาในการส่งอีเมล ไม่สามารถส่งอีเมลไปยัง "{0}" ได้',
    'throttled'             => 'มีคำขอจาก IP นี้มากเกินไป กรุณาลองใหม่ใน {0} วินาที',
    'notEnoughPrivilege'    => 'คุณไม่มีสิทธิ์ในการดำเนินการนี้',

    // JWT Exceptions
    'invalidJWT'     => 'token ไม่ถูกต้อง',
    'expiredJWT'     => 'token หมดอายุแล้ว',
    'beforeValidJWT' => 'token ยังไม่สามารถใช้งานได้',

    'email'           => 'อีเมล',
    'username'        => 'ชื่อผู้ใช้',
    'password'        => 'รหัสผ่าน',
    'passwordConfirm' => 'รหัสผ่าน (อีกครั้ง)',
    'haveAccount'     => 'มีบัญชีอยู่แล้ว?',
    'token'           => 'Token',

    // Buttons
    'confirm' => 'ยืนยัน',
    'send'    => 'ส่ง',

    // Registration
    'register'         => 'สมัครสมาชิก',
    'registerDisabled' => 'ขณะนี้ยังไม่เปิดให้สมัครสมาชิก',
    'registerSuccess'  => 'ยินดีต้อนรับ!',

    // Login
    'login'              => 'เข้าสู่ระบบ',
    'needAccount'        => 'ยังไม่มีบัญชี?',
    'rememberMe'         => 'จดจำฉันไว้',
    'forgotPassword'     => 'ลืมรหัสผ่าน?',
    'useMagicLink'       => 'ใช้ลิงก์เข้าสู่ระบบ',
    'magicLinkSubject'   => 'ลิงก์เข้าสู่ระบบของคุณ',
    'magicTokenNotFound' => 'ไม่สามารถยืนยันลิงก์ได้',
    'magicLinkExpired'   => 'ขออภัย ลิงก์หมดอายุแล้ว',
    'checkYourEmail'     => 'กรุณาตรวจสอบอีเมลของคุณ!',
    'magicLinkDetails'   => 'เราได้ส่งอีเมลที่มีลิงก์เข้าสู่ระบบให้คุณแล้ว ใช้ได้ภายใน {0} นาที',
    'magicLinkDisabled'  => 'ขณะนี้ไม่อนุญาตให้ใช้ MagicLink',
    'successLogout'      => 'ออกจากระบบเรียบร้อยแล้ว',
    'backToLogin'        => 'กลับไปหน้าเข้าสู่ระบบ',

    // Passwords
    'errorPasswordLength'       => 'รหัสผ่านต้องมีอย่างน้อย {0, number} ตัวอักษร',
    'suggestPasswordLength'     => 'การใช้วลี (ยาวได้ถึง 255 ตัวอักษร) ทำให้รหัสผ่านปลอดภัยและจำง่ายขึ้น',
    'errorPasswordCommon'       => 'รหัสผ่านต้องไม่เป็นรหัสที่ใช้กันทั่วไป',
    'suggestPasswordCommon'     => 'รหัสผ่านถูกตรวจสอบกับรหัสผ่านที่ใช้บ่อยหรือรั่วไหลกว่า 65,000 รายการ',
    'errorPasswordPersonal'     => 'รหัสผ่านต้องไม่มีข้อมูลส่วนตัว',
    'suggestPasswordPersonal'   => 'ไม่ควรใช้อีเมลหรือชื่อผู้ใช้ของคุณเป็นส่วนหนึ่งของรหัสผ่าน',
    'errorPasswordTooSimilar'   => 'รหัสผ่านคล้ายกับชื่อผู้ใช้มากเกินไป',
    'suggestPasswordTooSimilar' => 'อย่าใช้ส่วนหนึ่งของชื่อผู้ใช้ในรหัสผ่าน',
    'errorPasswordPwned'        => 'รหัสผ่าน {0} เคยรั่วไหลจากการละเมิดข้อมูล และพบ {1, number} ครั้งใน {2}',
    'suggestPasswordPwned'      => 'ไม่ควรใช้ {0} เป็นรหัสผ่าน หากใช้อยู่ที่ใดให้เปลี่ยนทันที',
    'errorPasswordEmpty'        => 'กรุณากรอกรหัสผ่าน',
    'errorPasswordTooLongBytes' => 'รหัสผ่านต้องไม่เกิน {param} ไบต์',
    'passwordChangeSuccess'     => 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว',
    'userDoesNotExist'          => 'ไม่สามารถเปลี่ยนรหัสผ่านได้ ไม่พบผู้ใช้',
    'resetTokenExpired'         => 'ขออภัย token สำหรับรีเซ็ตหมดอายุแล้ว',

    // Email Globals
    'emailInfo'      => 'ข้อมูลเกี่ยวกับผู้ใช้:',
    'emailIpAddress' => 'IP Address:',
    'emailDevice'    => 'อุปกรณ์:',
    'emailDate'      => 'วันที่:',

    // 2FA
    'email2FATitle'       => 'การยืนยันตัวตนสองขั้นตอน',
    'confirmEmailAddress' => 'ยืนยันอีเมลของคุณ',
    'emailEnterCode'      => 'ยืนยันอีเมลของคุณ',
    'emailConfirmCode'    => 'กรอกรหัส 6 หลักที่เราส่งไปยังอีเมลของคุณ',
    'email2FASubject'     => 'รหัสยืนยันตัวตนของคุณ',
    'email2FAMailBody'    => 'รหัสยืนยันตัวตนของคุณคือ:',
    'invalid2FAToken'     => 'รหัสไม่ถูกต้อง',
    'need2FA'             => 'คุณต้องทำการยืนยันตัวตนสองขั้นตอนให้เสร็จสิ้น',
    'needVerification'    => 'กรุณาตรวจสอบอีเมลเพื่อเปิดใช้งานบัญชี',

    // Activate
    'emailActivateTitle'    => 'เปิดใช้งานอีเมล',
    'emailActivateBody'     => 'เราได้ส่งอีเมลพร้อมรหัสยืนยันไปให้คุณแล้ว กรุณาคัดลอกรหัสมาวางด้านล่าง',
    'emailActivateSubject'  => 'รหัสเปิดใช้งานของคุณ',
    'emailActivateMailBody' => 'กรุณาใช้รหัสด้านล่างเพื่อเปิดใช้งานบัญชีและเริ่มใช้งานระบบ',
    'invalidActivateToken'  => 'รหัสไม่ถูกต้อง',
    'needActivate'          => 'คุณต้องยืนยันรหัสที่ส่งไปยังอีเมลเพื่อสมัครสมาชิกให้เสร็จสิ้น',
    'activationBlocked'     => 'คุณต้องเปิดใช้งานบัญชีก่อนเข้าสู่ระบบ',

    // Groups
    'unknownGroup' => '{0} ไม่ใช่กลุ่มที่ถูกต้อง',
    'missingTitle' => 'กลุ่มต้องมีชื่อ (title)',

    // Permissions
    'unknownPermission' => '{0} ไม่ใช่สิทธิ์ที่ถูกต้อง',
];
