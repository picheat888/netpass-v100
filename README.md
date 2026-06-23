# NetPass — Guest Wi-Fi Voucher System

ระบบจัดการ Voucher Wi-Fi สำหรับ Guest ภายในองค์กร ผู้ใช้งานสามารถขอ Voucher ได้ทันที ระบบออก Voucher พร้อม QR Code สำหรับเชื่อมต่อ Wi-Fi อัตโนมัติ แบ่งพื้นที่ให้บริการตาม Location และ SSID พร้อมระบบจัดการสมาชิกและคลัง Voucher สำหรับ Admin

---

## Features

### Admin
- **Dashboard** — ภาพรวมสต็อก voucher คงเหลือ, request วันนี้, กราฟรายสัปดาห์, กิจกรรมล่าสุด, สรุปแยกพื้นที่
- **Voucher History** — ประวัติการออก voucher ทั้งหมด กรองตาม Location / Status / Keyword พร้อม Pagination
- **Voucher Pool (Stock)** — คลัง voucher แยกตาม Location นำเข้า batch (วาง text) หรือเพิ่มทีละใบ แก้ไข/ลบ
- **Locations** — จัดการพื้นที่ให้บริการ (ชื่อไทย/อังกฤษ + SSID) CRUD ผ่าน Modal
- **Members** — จัดการบัญชีผู้ใช้ เพิ่ม/แก้ไข/เปิด-ปิดการใช้งาน กำหนด Role (Admin/User)
- **Profile** — แก้ไขชื่อ-นามสกุล/ตำแหน่ง และเปลี่ยนรหัสผ่าน

### User
- **My Voucher** — ประวัติ voucher ของตัวเอง กรองตาม Status / Keyword
- **Request Voucher** — Wizard 3 ขั้นตอน (เลือก Location → เลือกระยะเวลา → ยืนยัน) แสดง Ticket + QR Code สำหรับเชื่อมต่อ Wi-Fi ได้ทันที
- **Profile** — แก้ไขข้อมูลส่วนตัวและเปลี่ยนรหัสผ่าน

### ทั่วไป
- รองรับ **2 ภาษา** — ไทย / อังกฤษ สลับได้ทุกหน้า
- **Session Database** — ไม่มีปัญหา session file lock บน Windows/Apache
- Responsive — ใช้งานได้บน Mobile / Tablet / Desktop

---

## Requirements

| Component | Version |
|-----------|---------|
| PHP | 8.2+ |
| MariaDB / MySQL | 10.4+ / 8.0+ |
| Apache | 2.4+ (mod_rewrite required) |
| Composer | 2.x |

PHP Extensions ที่ต้องการ: `intl`, `mbstring`, `mysqli`, `json`

---

## Installation

```bash
# 1. Clone / วางโปรเจกต์ใน htdocs
#    DocumentRoot ชี้ไปที่โฟลเดอร์ public/

# 2. ติดตั้ง dependencies
composer install

# 3. คัดลอก environment file
cp env .env

# 4. แก้ค่าใน .env
#    app.baseURL     = 'http://localhost:8880/'
#    database.*      = ตั้งค่าฐานข้อมูล
#    encryption.key  (รัน: php spark key:generate)

# 5. สร้างตารางฐานข้อมูล
php spark migrate

# 6. สร้าง Admin account แรก
php spark shield:user create
#    แล้วเพิ่ม user เข้า group admin ใน database:
#    INSERT INTO auth_groups_users (user_id, `group`) VALUES (<id>, 'admin');
```

### Apache VirtualHost (ตัวอย่าง port 8880)

```apache
<VirtualHost *:8880>
    DocumentRoot "C:/xampp/htdocs/guest-wifi-v100/public"
    <Directory "C:/xampp/htdocs/guest-wifi-v100/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## Third-Party Libraries

| Library | ใช้ทำอะไร |
|---------|----------|
| [CodeIgniter 4](https://codeigniter.com) | PHP MVC Framework หลัก |
| [CodeIgniter Shield](https://shield.codeigniter.com) | Authentication & Authorization |
| [Bootstrap 5.3](https://getbootstrap.com) | UI Framework |
| [Bootstrap Icons 1.11](https://icons.getbootstrap.com) | Icon set |
| [jQuery](https://jquery.com) | DOM / dependency ของ DataTables |
| [DataTables](https://datatables.net) | ตารางข้อมูล (ค้นหา / เรียง / แบ่งหน้า) |
| [Tom Select](https://tom-select.js.org) | Dropdown ค้นหาได้ (เลือก Location ฯลฯ) |
| [Cropper.js](https://fengyuanchen.github.io/cropperjs) | ครอปรูป Avatar ตอนอัปโหลด |
| [QR Code Generator (Kazuhiko Arase)](https://github.com/kazuhikoarase/qrcode-generator) | สร้าง QR Code สำหรับ Wi-Fi |
| [html-to-image](https://github.com/bubkoo/html-to-image) | Export Voucher เป็นรูปภาพ |
| [Google Fonts — Prompt](https://fonts.google.com/specimen/Prompt) | Font หลัก (รองรับภาษาไทย) |
| [Poppins](https://fonts.google.com/specimen/Poppins) | Font หัวข้อ / UI |
| [Google Sans Code](https://fonts.google.com/specimen/Google+Sans+Code) | Font สำหรับ Voucher Code |
