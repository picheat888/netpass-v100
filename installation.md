# NetPass — Deploy บน Ubuntu + Nginx (Cloudflare Origin Cert + Cloudflare Tunnel)

| | |
|---|---|
| **Stack** | CodeIgniter 4.7 + Shield + MariaDB + PHP 8.3-FPM (render ฝั่ง server ล้วน — ไม่มี npm build, ไม่มี React/Vue) |
| **TLS** | Cloudflare Origin Certificate (อายุ 15 ปี ออกฟรีจาก Cloudflare) |
| **เข้าถึง** | ผ่าน Cloudflare Tunnel (cloudflared) — ไม่ต้องเปิด port 80/443 ออกเน็ต! |
| **สมมติ** | Ubuntu 22.04/24.04, deploy ที่ `/var/www/guest-wifi-v100`, โดเมน `netpass.example.com` |

---

## ภาพรวมการไหลของ traffic (เข้าใจก่อนลงมือ)

```
Browser ──HTTPS(edge cert ของ CF)──► Cloudflare
                                        │
                         Tunnel (ขาออก, เข้ารหัสอยู่แล้ว)
                                        ▼
เครื่อง Ubuntu:  cloudflared ──HTTPS(origin cert)──► nginx :443 ──► php-fpm ──► CI4 (public/)
```

**ข้อดีของ Tunnel:**

- เครื่อง server "ไม่ต้องมี public IP" และ "ไม่ต้องเปิด port เข้า" เลย — cloudflared วิ่งออก (outbound) ไปหา Cloudflare เอง → firewall ปิดขาเข้าได้หมด
- ทุก hop เข้ารหัส (browser→CF, CF→tunnel, cloudflared→nginx ด้วย origin cert)

---

## 1. ติดตั้ง Package ที่จำเป็น

```bash
sudo apt update && sudo apt upgrade -y

# Nginx + MariaDB
sudo apt install -y nginx mariadb-server

# PHP 8.3 + extensions ที่ CI4 + Shield ต้องใช้
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath

# Composer
sudo apt install -y composer

# (ไม่ต้องลง Node.js — โปรเจกต์นี้ render ฝั่ง server ไม่มีขั้นตอน build)
```

---

## 2. ตั้งค่า MariaDB

```bash
sudo mysql_secure_installation        # ตั้ง root password + ลบ test db (ตอบ Y ตามแนะนำ)
sudo mysql
```

```sql
-- ใน prompt:
CREATE DATABASE netpass CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'netpass_app'@'localhost' IDENTIFIED BY 'ตั้งรหัสแข็งแรงตรงนี้';  -- << อย่า commit รหัสจริงขึ้น git
GRANT ALL PRIVILEGES ON netpass.* TO 'netpass_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> ⚠️ ห้ามใช้รหัส dev ซ้ำ (ดู `docs/DEPLOYMENT.md` — รหัส dev ถือว่าหลุดแล้ว)

---

## 3. เอาโค้ดขึ้น Server

```bash
sudo mkdir -p /var/www/guest-wifi-v100
sudo chown -R $USER:$USER /var/www/guest-wifi-v100

git clone https://github.com/picheat888/netpass-v100.git /var/www/guest-wifi-v100
cd /var/www/guest-wifi-v100
```

---

## 4. ตั้งค่า .env (production + HTTPS)

```bash
cp env.production.example .env
nano .env
```

```ini
# ----- แก้ค่าสำคัญ -----
CI_ENVIRONMENT = production                      # ปิด stack trace/debug

app.baseURL = 'https://netpass.example.com/'     # << โดเมนจริง + https + ปิดท้ายด้วย /
app.forceGlobalSecureRequests = true             # บังคับ https

# URL สะอาด — ตัด index.php ออกจากลิงก์ (nginx/Apache ทำ rewrite ให้แล้ว)
app.indexPage = ''

# Session cookie ปลอดภัยเมื่อเสิร์ฟผ่าน HTTPS
cookie.secure = true                             # cookie ส่งผ่าน https เท่านั้น

# --- Database ---
database.default.hostname = localhost
database.default.database = netpass
database.default.username = netpass_app
database.default.password = รหัสที่ตั้งใน step 2  # << ตรงกับ step 2 (อย่า commit รหัสจริง)
database.default.DBDriver = MySQLi
database.default.port = 3306

encryption.key = CHANGE_ME                       # << step 5 จะ generate ทับให้
```

---

## 5. ติดตั้ง Dependencies + Database

```bash
cd /var/www/guest-wifi-v100

composer install --no-dev --optimize-autoloader
php spark key:generate                  # เขียน encryption.key ลง .env ให้อัตโนมัติ

php spark migrate --all                 # รัน migration ทั้งหมด (App + Shield) → ตารางว่าง ไม่มีสมาชิก
```

> **บน prod ไม่ต้อง seed บัญชี demo** — seeder ในโปรเจกต์ (`NetPassSeeder`/`DemoCredsSeeder`) gate ไว้เฉพาะ development และตั้งรหัสอ่อน (admin/admin, user/user) → ห้ามใช้บน prod ให้สร้าง admin ตัวจริงเองด้วย 3 คำสั่งด้านล่างแทน

```bash
# สร้าง admin ตัวจริง — shield:user create จะให้กลุ่ม default = user เสมอ
php spark shield:user create                            # ตั้ง username/email/รหัสแข็งแรง
php spark shield:user addgroup   -n <username> -g admin  # เลื่อนเป็น admin
php spark shield:user removegroup -n <username> -g user  # เอาออกจากกลุ่ม user ให้เหลือ admin ล้วน
```

---

## 6. ตั้ง Permission ให้ www-data

```bash
sudo chown -R www-data:www-data /var/www/guest-wifi-v100
sudo find /var/www/guest-wifi-v100 -type d -exec chmod 755 {} \;
sudo find /var/www/guest-wifi-v100 -type f -exec chmod 644 {} \;

# writable/ ต้องเขียนได้ (log, cache, session, อัปโหลด)
sudo chmod -R 775 /var/www/guest-wifi-v100/writable
```

---

## 7. ออก Cloudflare Origin Certificate

1. Login `dash.cloudflare.com` → เลือก Domain
2. **SSL/TLS → Origin Server → Create Certificate**
3. ตั้งค่า:
   - Key type — RSA (2048)
   - Hostnames — `netpass.example.com` และ `*.example.com`
   - Expiration — 15 years
4. Create แล้ว Copy ทั้ง 2 ก้อน (ดูได้ครั้งเดียว!):
   - Origin Certificate → `cert.pem`
   - Private Key → `privatekey.pem`
5. วางลงเครื่อง server:

   ```bash
   sudo mkdir -p /etc/nginx/ssl
   sudo nano /etc/nginx/ssl/cert.pem            # วาง Origin Certificate
   sudo nano /etc/nginx/ssl/privatekey.pem      # วาง Private Key
   sudo chmod 600 /etc/nginx/ssl/privatekey.pem
   ```

6. ที่ dashboard: **SSL/TLS → Overview** → ตั้ง mode เป็น **Full (strict)**

---

## 8. ตั้งค่า Nginx

```bash
sudo nano /etc/nginx/sites-available/netpass
```

```nginx
server {
    listen 80;
    server_name netpass.example.com localhost;   # << โดเมนจริง
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name netpass.example.com localhost;

    root /var/www/guest-wifi-v100/public;         # *** document root ชี้ที่ public/ เท่านั้น ***
    index index.html index.php;

    # Cloudflare Origin Certificate
    ssl_certificate     /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/privatekey.pem;

    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    charset utf-8;
    client_max_body_size 50M;

    # CodeIgniter 4 routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # กันเข้าถึงไฟล์ซ่อน (.env, .git ฯลฯ)
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # Cloudflare Real IP
    set_real_ip_from 103.21.244.0/22;
    set_real_ip_from 103.22.200.0/22;
    set_real_ip_from 103.31.4.0/22;
    set_real_ip_from 104.16.0.0/13;
    set_real_ip_from 104.24.0.0/14;
    set_real_ip_from 108.162.192.0/18;
    set_real_ip_from 131.0.72.0/22;
    set_real_ip_from 141.101.64.0/18;
    set_real_ip_from 162.158.0.0/15;
    set_real_ip_from 172.64.0.0/13;
    set_real_ip_from 173.245.48.0/20;
    set_real_ip_from 188.114.96.0/20;
    set_real_ip_from 190.93.240.0/20;
    set_real_ip_from 197.234.240.0/22;
    set_real_ip_from 198.41.128.0/17;
    real_ip_header CF-Connecting-IP;
}
```

```bash
# เปิดใช้งาน + ตรวจ + reload
sudo ln -s /etc/nginx/sites-available/netpass /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## 9. ติดตั้ง Cloudflare Tunnel (cloudflared)

### 9.1 ติดตั้ง cloudflared (บน server)

```bash
# Add cloudflare gpg key
sudo mkdir -p --mode=0755 /usr/share/keyrings
curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg | sudo tee /usr/share/keyrings/cloudflare-main.gpg >/dev/null

# Add this repo to your apt repositories
echo 'deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared any main' | sudo tee /etc/apt/sources.list.d/cloudflared.list

# install cloudflared
sudo apt-get update && sudo apt-get install cloudflared
```

### 9.2 Login Cloudflare เพื่อเอา token (ทำบน browser เครื่องอื่น)

```bash
cloudflared tunnel login
```

1. คัดลอก URL ไปเปิดบน browser เครื่องอื่น
2. Login เข้า Cloudflare → เลือก Domain

### 9.3 สร้าง Tunnel

```bash
cloudflared tunnel create <Tunnel Name>
```

### 9.4 เขียน config.yml (ตั้ง TLS ได้เต็มที่ตรงนี้)

```bash
sudo mkdir /etc/cloudflared
sudo nano /etc/cloudflared/config.yml
```

```yaml
tunnel: <TUNNEL_ID>
credentials-file: /root/.cloudflared/<TUNNEL_ID>.json

ingress:
  - hostname: <YOUR_DOMAIN>
    service: https://localhost:443
    originRequest:
      originServerName: <YOUR_DOMAIN>             # ให้ SNI ตรงกับ origin cert
      noTLSVerify: true
  - service: http_status:404
```

### 9.5 รัน cloudflared เป็น service (อ่าน config.yml — ไม่ใส่ token)

```bash
sudo cloudflared service install          # ติดตั้งแบบ "ไม่มี token" → service จะอ่าน /etc/cloudflared/config.yml
sudo systemctl enable --now cloudflared
sudo systemctl status cloudflared         # ต้องขึ้น active (running) + เห็น "Registered tunnel connection"
```

```bash
# ถ้า status ฟ้องหา config: เช็คว่าไฟล์อยู่ /etc/cloudflared/config.yml
# ดู log สด:
sudo journalctl -u cloudflared -f
```

### 9.6 ทำ DNS บน Cloudflare (ค่อยทำขั้นนี้ทีหลังได้)

ตอนนี้ tunnel วิ่งแล้ว แต่ยังไม่มีโดเมนชี้เข้ามา — เพิ่ม CNAME เองบน Dashboard:

**Cloudflare Dashboard → เลือกโดเมน `example.com` → DNS → Records → Add record**

- Type — `CNAME`
- Name — `netpass` (= subdomain)
- Target — `<TUNNEL_ID>.cfargotunnel.com`
- Proxy — **Proxied (เมฆส้ม) ** ต้องเปิด **

รอ DNS propagate ครู่หนึ่ง → เปิด `https://netpass.example.com` ได้เลย

---

## 10. Firewall (tunnel ไม่ต้องเปิด port ขาเข้า)

```bash
sudo ufw allow OpenSSH        # เปิดแค่ ssh ไว้รีโมต
sudo ufw enable
# ไม่ต้อง allow 80/443 — cloudflared วิ่งขาออกอย่างเดียว ปลอดภัยกว่าเปิด web port ตรงๆ
```

> **ทดสอบ:** เปิด `https://netpass.example.com` บน browser → ต้องเจอหน้า login NetPass

---

## อัปเดตโค้ดครั้งถัดไป

```bash
cd /var/www/guest-wifi-v100
git pull
composer install --no-dev --optimize-autoloader
php spark migrate --all
sudo chown -R www-data:www-data /var/www/guest-wifi-v100
sudo chmod -R 775 /var/www/guest-wifi-v100/writable
sudo systemctl reload php8.3-fpm
# (origin cert อายุ 15 ปี + tunnel auto-start — ไม่ต้องทำอะไรเพิ่มตอน deploy)
```

---

## ข้อควรระวัง / แก้ปัญหาที่เจอบ่อย

1. **`app.baseURL` ต้องเป็น `https://` และตรงกับโดเมนที่ผูก tunnel** — ถ้าไม่ตรง asset/redirect/cookie จะเพี้ยน
2. **`forceGlobalSecureRequests=true` + `cookie.secure=true`** ใช้ได้เพราะ nginx เสิร์ฟ https (cloudflared ต่อเข้าด้วย `https://localhost:443`) — ถ้าเปลี่ยนไปต่อ http ต้องปิดสองค่านี้
3. **cloudflared status ไม่ขึ้น running:**
   - เช็ค `credentials-file` path ใน config.yml ว่าตรงกับไฟล์ `<TUNNEL_ID>.json` จริง
   - ดู log: `sudo journalctl -u cloudflared -f`
4. **หน้า 502 Bad Gateway:**
   - nginx ไม่ขึ้น / port 443 ไม่ฟัง → `sudo nginx -t && sudo systemctl status nginx`
   - เช็ค: `curl -k https://localhost:443` บนเครื่อง server ต้องได้ HTML
5. **หน้า error 403 / writable เขียนไม่ได้:**
   - `sudo chmod -R 775 /var/www/guest-wifi-v100/writable`
   - `sudo chown -R www-data:www-data /var/www/guest-wifi-v100/writable`
6. **อย่า commit** `.env`, `/etc/nginx/ssl/privatekey.pem`, `<TUNNEL_ID>.json` ขึ้น git
7. **ก่อน go-live** เช็ค checklist ความปลอดภัยใน `docs/DEPLOYMENT.md` ให้ครบทุกข้อ
