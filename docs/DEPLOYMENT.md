# Deployment Security Checklist (NetPass)

ก่อน deploy ขึ้น production ต้องทำครบทุกข้อ:

- [ ] คัดลอก `env.production.example` → `.env` แล้วเติมค่าจริง (อย่าใช้ค่า dev)
- [ ] `CI_ENVIRONMENT = production` (ปิด stack trace/debug)
- [ ] `app.forceGlobalSecureRequests = true` + `cookie.secure = true` (ต้องมี TLS ก่อน)
- [ ] สร้าง encryption key ใหม่: `php spark key:generate`
- [ ] เปลี่ยนรหัส DB ใหม่ ไม่ซ้ำกับ dev (`Itservice@224441` ถือว่าหลุดแล้ว — ห้ามใช้ซ้ำ)
- [ ] รหัส admin ตั้งใหม่ให้แข็งแรง + `force_reset = 1` (ห้ามคง admin/admin)
- [ ] ห้ามรัน `DemoCredsSeeder` / seed บัญชี demo บน production (ดู gate ใน Task B2)
- [ ] document root ชี้ที่ `public/` เท่านั้น (`app/`, `writable/`, `.env` อยู่นอก web root)
- [ ] ยืนยัน CSRF เปิด (Task A1) และ self-registration ปิด (Task A2)
- [ ] ลบ log เก่าใน `writable/logs/` (เคยมี `[DBG-pwd]` artifacts)

## CSP (Content-Security-Policy) — งานในอนาคต
ตอนนี้ปิดไว้ (`App::$CSPEnabled=false`) เพราะแอปมี inline script/style จำนวนมาก
การเปิด CSP ต้อง: (1) ย้าย inline JS/CSS ออกเป็นไฟล์ หรือใส่ nonce ทุกบล็อก
(2) ตั้ง `ContentSecurityPolicy` config ค่อยเป็นค่อยไปด้วย report-only ก่อน
output encoding ปัจจุบันใช้ esc() สม่ำเสมอ ความเสี่ยง XSS จึงต่ำ — CSP เป็น defense-in-depth
