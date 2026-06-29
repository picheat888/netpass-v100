# NetPass — Guest Wi-Fi Voucher System

ระบบจัดการ Voucher Wi-Fi สำหรับ Guest ภายในองค์กร ผู้ใช้งานขอ Voucher ได้ทันที ระบบออก Voucher พร้อม QR Code สำหรับเชื่อมต่อ Wi-Fi อัตโนมัติ แบ่งพื้นที่ให้บริการตาม Location และ SSID พร้อมระบบจัดการสมาชิกและคลัง Voucher สำหรับ Admin รองรับ 2 ภาษา (ไทย/อังกฤษ)

---

## Features

### Admin
- **Dashboard** — ภาพรวม voucher คงเหลือ, request วันนี้, กราฟรายสัปดาห์, กิจกรรมล่าสุด, สรุปแยกพื้นที่
- **Voucher History** — ประวัติการออก voucher ทั้งหมด กรองตาม Location / Status / Keyword, พิมพ์ Ticket หลายใบพร้อมกัน
- **Voucher Pool (Stock)** — คลัง voucher แยกตาม Location, นำเข้าแบบ batch (ดาวน์โหลด template + import) หรือเพิ่มทีละใบ, แก้ไข/ลบ
- **Locations** — จัดการพื้นที่ให้บริการ (ชื่อไทย/อังกฤษ + SSID) แบบ CRUD ผ่าน Modal
- **Members** — จัดการบัญชีผู้ใช้ เพิ่ม/แก้ไข/เปิด-ปิดการใช้งาน, กำหนด Role (Admin/User), reset password, นำเข้าแบบ batch
- **Profile** — แก้ไขข้อมูลส่วนตัว/ตำแหน่ง, อัปโหลด+ครอป Avatar, เปลี่ยนรหัสผ่าน

### User
- **Request Voucher** — Wizard 3 ขั้นตอน (เลือก Location → เลือกระยะเวลา → ยืนยัน) แสดง Ticket + QR Code เชื่อมต่อ Wi-Fi ได้ทันที
- **My Voucher** — ประวัติ voucher ของตัวเอง กรองตาม Status / Keyword, พิมพ์ Ticket
- **Profile** — แก้ไขข้อมูลส่วนตัวและเปลี่ยนรหัสผ่าน

### ทั่วไป
- รองรับ **2 ภาษา** — ไทย / อังกฤษ สลับได้ทุกหน้า 
- **บังคับเปลี่ยนรหัสผ่าน** ครั้งแรกที่ล็อกอิน (force password reset)
- **Session แบบ Database** — ไม่มีปัญหา session file lock บน Windows/Apache
- Responsive — ใช้งานได้บน Mobile / Tablet / Desktop

---

## Tech Stack

| Layer | เทคโนโลยี |
|-------|-----------|
| Backend | PHP 8.2+ + CodeIgniter 4 |
| Frontend | CI4 Views + Bootstrap 5 |
| Database | MariaDB / MySQL |
| Auth | CodeIgniter Shield |
| Session | Database handler |

---

## Requirements

| Component | Version |
|-----------|---------|
| OS | Ubuntu Server 24.04 LTS |
| PHP | 8.2+ |
| MariaDB / MySQL | 10.4+ / 8.0+ |
| Web Server | Nginx |
| Composer | 2.x |

PHP Extensions ที่ต้องการ: `intl`, `mbstring`, `mysqli`, `json`

---

## Installation

ติดตั้งบน **Ubuntu Server 24.04 LTS**

> สำหรับ deploy แบบเต็ม (Nginx + Cloudflare Origin Cert + Cloudflare Tunnel) ดู [`installation.md`](installation.md)

```bash
# 1. ติดตั้ง package ที่ต้องใช้
sudo apt update
sudo apt install -y nginx mariadb-server git unzip \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-intl php8.3-mbstring php8.3-curl

# 2. ติดตั้ง Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 3. ตั้งค่าฐานข้อมูล
sudo mysql_secure_installation        # ตั้งรหัส root + ปิด remote root
sudo mysql -e "CREATE DATABASE netpass CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'netpass'@'localhost' IDENTIFIED BY 'CHANGE_ME';"                <------   เปลี่ยน  CHANGE_ME  เป็นรหัสที่ตั้งใน DB
sudo mysql -e "GRANT ALL PRIVILEGES ON netpass.* TO 'netpass'@'localhost'; FLUSH PRIVILEGES;"

# 4. วางโปรเจกต์
sudo git clone <repo-url> /var/www/guest-wifi-v100
cd /var/www/guest-wifi-v100

# 5. ติดตั้ง dependencies (production — ไม่เอา dev package)
composer install --no-dev --optimize-autoloader

# 6. ตั้งค่า .env สำหรับ production
cp env.production.example .env
#    CI_ENVIRONMENT = production
#    app.baseURL    = 'https://netpass.example.com/'
#    app.indexPage  = ''                          # ตัด index.php ออกจาก URL
#    database.default.hostname = localhost
#    database.default.database = netpass
#    database.default.username = netpass
#    database.default.password = CHANGE_ME        <------   เปลี่ยน  CHANGE_ME  เป็นรหัสที่ตั้งใน DB
php spark key:generate

# 7. สร้างตาราง (production: migrate อย่างเดียว ไม่ seed demo account)
php spark migrate --all

# 8. สร้าง admin account แรก แล้วเลื่อนเป็น group admin
php spark shield:user create                              # ตั้ง username/email/รหัสแข็งแรง
php spark shield:user addgroup   -n <username> -g admin   # เลื่อนเป็น admin
php spark shield:user removegroup -n <username> -g user   # เอาออกจากกลุ่ม user

# 9. ตั้งสิทธิ์โฟลเดอร์ให้ web server เขียน writable/ ได้
sudo chown -R www-data:www-data /var/www/guest-wifi-v100
sudo find /var/www/guest-wifi-v100/writable -type d -exec chmod 775 {} \;

# 10. ติดตั้ง nginx config (ดูตัวอย่างด้านล่าง) แล้ว reload
sudo ln -s /etc/nginx/sites-available/netpass /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

> หลัง deploy แนะนำติดตั้ง HTTPS ด้วย `sudo apt install certbot python3-certbot-nginx && sudo certbot --nginx`

### Nginx Server Block

สร้างไฟล์ `/etc/nginx/sites-available/netpass` (ก่อนรัน `ln -s` ในขั้นตอนที่ 10):

```nginx
server {
    listen 80;
    server_name netpass.example.com;
    root /var/www/guest-wifi-v100/public;   # ชี้ที่ public/ เท่านั้น
    index index.php;

    # ส่งทุก request ที่ไม่ใช่ไฟล์จริงเข้า front controller
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # ส่งไฟล์ PHP ให้ PHP-FPM
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;   # Ubuntu 24.04 = php8.3
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # บล็อกการเข้าถึงไฟล์ซ่อน (เช่น .env, .git)
    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

> Document root ต้องชี้ที่ `public/` เสมอ เพื่อไม่ให้ `.env`, `app/`, `writable/` เข้าถึงได้จากภายนอก

---

## Default Login (เฉพาะ development)

`NetPassSeeder` จะสร้างบัญชี demo ให้เมื่อ `CI_ENVIRONMENT = development` เท่านั้น:

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin` |
| User | `user` | `user` |

> ⚠️ บัญชีเหล่านี้มีไว้ทดสอบเท่านั้น **ห้ามใช้บน production** — ดูหัวข้อ Security Notes

---

## Security Notes

- **เปลี่ยน default password** ของ admin/user ทันทีก่อนขึ้น production (หรืออย่ารัน seeder บน production)
- ตั้ง `CI_ENVIRONMENT = production` บน server จริง — ปิดการสร้าง demo account และซ่อน error detail
- **ห้าม commit `.env`** ขึ้น git (มีรหัส DB + encryption key)
- รัน `php spark key:generate` ให้ได้ key ของตัวเองทุก environment
- assets ทั้งหมดโหลดจากเครื่อง (self-hosted) ไม่พึ่ง CDN ภายนอก

---

## Third-Party Libraries

ทุก library โหลดแบบ local (self-hosted) จาก `public/assets/` — ไม่พึ่ง CDN

| Library | ใช้ทำอะไร |
|---------|----------|
| [CodeIgniter 4](https://codeigniter.com) | PHP MVC Framework หลัก |
| [CodeIgniter Shield](https://shield.codeigniter.com) | Authentication & Authorization |
| [Bootstrap 5](https://getbootstrap.com) | UI Framework |
| [Bootstrap Icons](https://icons.getbootstrap.com) | Icon set |
| [jQuery](https://jquery.com) | DOM / dependency ของ DataTables |
| [DataTables](https://datatables.net) | ตารางข้อมูล (ค้นหา / เรียง / แบ่งหน้า) |
| [Tom Select](https://tom-select.js.org) | Dropdown ค้นหาได้ (เลือก Location ฯลฯ) |
| [Cropper.js](https://fengyuanchen.github.io/cropperjs) | ครอปรูป Avatar ตอนอัปโหลด |
| [QR Code Generator (Kazuhiko Arase)](https://github.com/kazuhikoarase/qrcode-generator) | สร้าง QR Code สำหรับ Wi-Fi |
| [html-to-image](https://github.com/bubkoo/html-to-image) | Export Voucher เป็นรูปภาพ |