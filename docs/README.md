# Finance Tracker (PHP + MySQL)

เว็บแอประบบบันทึกรายรับ–รายจ่าย พร้อมสรุปผลรายเดือน รองรับการล็อกอินปกติและ Google OAuth (เลือกใช้ได้) โค้ดเป็น PHP (no framework) เชื่อมต่อฐานข้อมูล MySQL ผ่าน PDO

> เหมาะสำหรับเดสก์ท็อป/โน้ตบุ๊ก และสามารถใช้ผ่านเบราว์เซอร์บนมือถือได้

---

## คุณสมบัติ (Features)
- สมัครสมาชิก/ล็อกอิน/ล็อกเอาต์ (Session-based)  
- ล็อกอินผ่าน Google (OAuth 2.0)
- เพิ่ม/ลบ/ดู รายการ **รายรับ** และ **รายจ่าย** พร้อมหมวดหมู่  
- หน้าสรุปผลรายเดือน: ยอดรวมรายรับ/รายจ่าย และคงเหลือ  
- โปรไฟล์ผู้ใช้ (เก็บชื่อ-นามสกุล รูปโปรไฟล์ได้จากฐานข้อมูล)  
- ป้องกันการเข้าถึงหน้าภายในด้วย `requireLogin()`

---

## โครงสร้างโปรเจกต์ (สำคัญ)
```
finance_tracker/
├─ add_transaction.php        # ฟอร์มเพิ่มรายการ
├─ index.php                  # Dashboard / สรุปผล
├─ login.php                  # หน้าล็อกอิน
├─ logout.php                 # ออกจากระบบ
├─ register.php               # สมัครสมาชิก
├─ transactions.php           # ดู/จัดการรายการทั้งหมด
├─ auth/
│  ├─ google_login.php        # เริ่มต้น OAuth Login (ตัวอย่าง)
│  └─ google_register.php     # เชื่อมบัญชี Google (ตัวอย่าง)
├─ backend/
│  ├─ config.php              # ตั้งค่าฐานข้อมูล + ฟังก์ชัน session helper
│  └─ env/                    # โฟลเดอร์สำหรับไฟล์ลับ (เช่น client secret)
├─ database/
│  └─ db.sql                  # สคริปต์สร้างฐานข้อมูล/ตาราง
├─ src/logo/                  # โลโก้
└─ styles/                    # ไฟล์ CSS
│  ├─ add_styles.css          # css หน้าเพิ่มข้อมูล
│  ├─ index_styles.css        # css หน้าหลัก
│  ├─ login_styles.css        # css หน้าล็อคอิน
|  └─ register_styles.css     # css หน้าลงทะเบียน
└─ └─ transactions_styles.css # css หน้าดูค่าใช้จ่าย
```

> **หมายเหตุ:** ใน `backend/config.php` มีตัวแปรเชื่อมต่อ DB เช่น `$host`, `$dbname`, `$username`, `$password` และอาร์เรย์หมวดหมู่พื้นฐาน

---

## เทคโนโลยีที่ใช้
- PHP 8+ (ทำงานได้กับ 7.4 ขึ้นไปในหลายกรณี)
- MySQL 8.x (หรือ MariaDB เทียบเท่า)
- Bootstrap 5 (CDN)
- Google Fonts: Kanit
- (ออปชัน) Google OAuth 2.0

---

## การติดตั้งและรัน (Local)
### 1) เตรียมเครื่องมือ
- ติดตั้ง **XAMPP/LAMP/WAMP** หรือ PHP + MySQL แยกเอง
- สร้างฐานข้อมูล MySQL และเปิดใช้งาน InnoDB

### 2) สร้างฐานข้อมูลจากสคริปต์
เข้า MySQL แล้วรันไฟล์ `database/db.sql`  
ตัวอย่าง:
```sql
SOURCE /absolute/path/to/finance_tracker/database/db.sql;
```

### 3) ตั้งค่าเชื่อมต่อฐานข้อมูล
แก้ไขไฟล์ `backend/config.php`
```php
$host = 'localhost';
$dbname = 'finance_tracker';
$username = 'root';
$password = '';
```
ให้ตรงกับเครื่องของคุณ

### 4) (ออปชัน) ตั้งค่า Google OAuth
- สร้าง Google OAuth Client (Web)
- ตั้งค่า Authorized redirect URI ให้ชี้มาที่ `http://localhost/finance_tracker/auth/google_login.php` (ปรับตามโฮสต์จริง)
- เก็บ `client_id` และ `client_secret` ไว้ในไฟล์ภายใต้ `backend/env/` แล้วอ่านค่าในสคริปต์ `auth/*.php`

### 5) รันแอป
วางโฟลเดอร์ `finance_tracker/` ไว้ในโฟลเดอร์เว็บเซิร์ฟเวอร์ (เช่น `htdocs/` บน XAMPP)  
จากนั้นเปิดเบราว์เซอร์ไปที่:
```
http://localhost/finance_tracker/login.php
```


---

## ตารางหลักในฐานข้อมูล (สรุปย่อ)
> ดูรายละเอียดเต็มใน `database/db.sql`

- `users` — เก็บข้อมูลผู้ใช้ (local/google), อีเมล, รหัสผ่าน (แนะนำ **hash** ก่อนบันทึก), รูปโปรไฟล์, สถานะต่าง ๆ
- `user_sessions` — จัดเก็บ session (ตามสคีมาที่เตรียมไว้)
- `oauth_tokens` — เก็บ access/refresh token สำหรับบัญชี Google
- `transactions` — รายการรายรับ/รายจ่าย (amount, type, category, note, created_at, user_id)

> ใน `config.php` มี `$categories` สำหรับแม็ปหมวดหมู่ `expense`/`income`

---


## การใช้งาน

### การสมัครสมาชิก
1. คลิก "สมัครสมาชิก" ในหน้าเข้าสู่ระบบ
2. กรอกข้อมูลส่วนตัว (ชื่อ, username, email, password)
3. ระบบจะสร้างหมวดหมู่เริ่มต้นให้อัตโนมัติ

### การเข้าสู่ระบบ
- ใช้ username/email กับ password
- หรือเข้าสู่ระบบด้วย Google

### การบันทึกรายการ
1. ไปหน้า "เพิ่มรายการ"
2. เลือกประเภท (รายรับ/รายจ่าย)
3. เลือกหมวดหมู่
4. ระบุจำนวนเงินและรายละเอียด
5. เลือกวันที่ทำรายการ

### การดูข้อมูลสรุป
- หน้าแรกแสดงข้อมูลสรุปรายเดือน
- กราฟวงกลมแสดงสัดส่วนค่าใช้จ่าย
- รายการล่าสุด 10 รายการ

---

## เทคโนโลยีที่ใช้

### Frontend
- **HTML5/CSS3**: โครงสร้างและการตกแต่ง
- **Bootstrap 5**: Framework สำหรับ responsive design
- **Font Awesome**: ไอคอน
- **Chart.js**: แสดงกราฟข้อมูล
- **Google Fonts**: ฟอนต์ Kanit

### Backend
- **PHP 7.4+**: ภาษาโปรแกรมหลัก
- **MySQL**: ฐานข้อมูล
- **PDO**: การเชื่อมต่อฐานข้อมูล
- **Sessions**: การจัดการ authentication

### การรักษาความปลอดภัย
- Password hashing ด้วย PHP password functions
- Prepared statements ป้องกัน SQL injection
- CSRF protection ในการ OAuth
- Session security

---

## โครงสร้างฐานข้อมูล

### ตารางหลัก
- **users**: ข้อมูลผู้ใช้และการเข้าสู่ระบบ
- **transactions**: ข้อมูลธุรกรรมรายรับ-รายจ่าย
- **categories**: หมวดหมู่รายการ (ขยายได้ในอนาคต)
- **budgets**: งบประมาณ (ขยายได้ในอนาคต)

### Views และ Procedures
- **user_summary**: สรุปข้อมูลผู้ใช้และยอดรวม
- **monthly_summary**: สรุปรายเดือน
- **CreateDefaultCategories()**: สร้างหมวดหมู่เริ่มต้น

---

## Author
Wachirawit Raksa
(26/08/2025)
