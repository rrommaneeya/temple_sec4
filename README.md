# Section 4 — Person MVC

เริ่มต้นพบเพียง index.php (ทดสอบการเชื่อมต่อ) และ config/database.php; ไม่มี MVC เดิม หน้าเว็บกลุ่มใน database_project_g11 ไม่ได้แก้ไข

## การทำงาน

- index.php เรียก bootstrap.php เพื่อเปิด session, เชื่อม PDO และเลือก PersonController
- Controller ตรวจ action, CSRF, ข้อมูลกรอก และเลือก View หรือ redirect 303 หลังบันทึก
- models/PersonModel.php ใช้ prepared statements สำหรับ CRUD และ Search
- views/layout.php และ views/person/* แสดงผลโดย escape HTML; assets/style.css จัดหน้าจอ
- เลขประชาชนว่างถูกเก็บเป็น NULL; ค่าไม่ว่างต้องเป็นตัวเลข 13 หลัก (ไม่ได้ตรวจ checksum)
- UNIQUE ของ MySQL ป้องกันเลขซ้ำ และ Foreign Key ปฏิเสธการลบข้อมูลที่ถูกอ้างอิง โดยแสดงข้อความที่อ่านเข้าใจได้

## การตรวจสอบ

PHP ในเครื่อง: 8.0.30 ตรวจ syntax ทุกไฟล์ได้ การเชื่อมต่อ config เดิมในเครื่องไม่สำเร็จ จึงยังไม่ได้ยืนยัน version, schema, constraints หรือ CRUD ของ MySQL จริง และไม่ได้แก้ไขฐานข้อมูลกลาง

รัน `C:\xampp\php\php.exe tests/person.php` เพื่อตรวจ Model กับ SQLite ชั่วคราวในหน่วยความจำ รวมถึง validation และ escaping การทดสอบนี้ไม่แทน MySQL integration

บน Server ให้ทดสอบด้วยข้อมูลทดสอบที่แยกจากข้อมูลใช้งาน:

1. เปิด index.php?page=person แล้วเพิ่มบุคคลโดยเว้นเลขประชาชน ทดสอบเพิ่มได้มากกว่าหนึ่งคน
2. เพิ่มเลข 13 หลัก แล้วใช้เลขเดิมซ้ำ ต้องมีข้อความแจ้งและไม่เพิ่มข้อมูล
3. ค้นหาด้วยชื่อ นามสกุล ชื่อเต็ม เลขประชาชน และประเภท รวมถึงค้นหาไม่พบ
4. แก้ไขแล้ว refresh ต้องไม่บันทึกซ้ำ ทดสอบชื่อว่างและเลขผิดรูปแบบทั้งฝั่ง browser และ POST
5. ใส่ข้อความ HTML ในชื่อ ต้องแสดงเป็นข้อความ ไม่รัน script
6. ลบข้อมูลทดสอบที่ไม่มี reference และทดสอบบุคคลที่มี reference ต้องถูกปฏิเสธ ตรวจ FK จริงทุกตารางที่อ้าง person ก่อนใช้ production
7. ส่ง POST โดยไม่มี CSRF ต้องตอบ 403; เปิด action=delete ด้วย GET ต้องตอบ 405
8. ตรวจว่าเมื่อ DB ใช้งานไม่ได้ เว็บไม่แสดง DSN, SQL หรือรหัสผ่าน

## Deploy ผ่าน FileZilla

เชื่อม SFTP ด้วยบัญชีของตนเอง ไปที่ /home/myweb/www/db26/db26_091/sec4/ สำรองไฟล์เดิมก่อนอัปโหลด

อัปโหลด index.php, bootstrap.php, helpers.php และโฟลเดอร์ models, controllers, views, assets โดยรักษาโครงสร้าง ไม่ต้องอัปโหลด tests หรือ README

เก็บ config/database.php ของ Server ไว้เมื่อเชื่อมต่อได้แล้ว ห้ามอัปโหลด config ของเครื่องทับโดยไม่ตรวจสอบ ไฟล์ config นี้ต้องมี getDBConnection() คืน PDO ที่ใช้ utf8mb4 ห้ามนำ credentials ขึ้น GitHub (.gitignore เพิ่มให้แล้ว แต่ไม่ป้องกันไฟล์ที่ถูก track ไปก่อนหน้า)

เปิดหน้า sec4/index.php?page=person และทำรายการทดสอบด้านบน ตรวจ PHP version บน hosting ผ่านแผงควบคุมหรือ SSH โดยไม่เผยแพร่ phpinfo บนเว็บ ปิด display_errors ใน PHP hosting ด้วย

## งานต่อไป

Party CRUD, Donation CRUD และ Dashboard ยังไม่ได้ทำในรอบ Person นี้ จึงยังไม่ครบเกณฑ์ 3 CRUD + 1 report ต้องยืนยัน Person บน MySQL จริงก่อน ขั้นต่อไปใช้ event.event_name ตาม Untitled.sql และไม่ต้องนำ SQL CREATE TABLE ไป import ซ้ำ
