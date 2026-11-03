# Party Management

## ไฟล์และ MVC

- models/PartyModel.php: CRUD, Search, ดึง Dropdown จาก person และ event JOIN temple, ตรวจรายการซ้ำ และตรวจ donation ก่อนเปลี่ยนกิจกรรม
- controllers/PartyController.php: ตรวจ input/Foreign Key/CSRF, เรียก Model แล้วเลือก View หรือ redirect 303
- views/party/index.php และ form.php: รายการ ค้นหา ฟอร์ม Dropdown และยืนยันลบ ใช้ h() escape ข้อมูลทุกครั้ง
- bootstrap.php: เพิ่ม router page=party
- helpers.php: เพิ่ม partyUrl()
- views/layout.php: เพิ่มเมนูและเลือก View ของแต่ละระบบ
- assets/style.css: เพิ่มรูปแบบ Dropdown

ไม่ได้แก้ config/database.php หรือ schema ฐานข้อมูลกลาง SQL อ้างอิง event.event_name และ temple.temple_name จาก Untitled.sql ที่ให้มา ยังไม่ได้ยืนยัน schema จริงของสองตารางนี้บน Server

## อัปโหลด

สำรองไฟล์ Server ก่อน แล้วอัปโหลดไฟล์ทั้งหมด 8 ไฟล์ด้านบน ไปยัง /home/myweb/www/db26/db26_091/sec4/ โดยรักษาโครงสร้างโฟลเดอร์ ไม่ต้องอัปโหลด tests, PARTY.md หรือ config/database.php

เปิด index.php?page=party หรือเลือกเมนูผู้เข้าร่วมกิจกรรม ต้องมี person, temple และ event ที่สัมพันธ์กันอยู่แล้ว หากไม่มีข้อมูลกิจกรรม ให้สมาชิกผู้ดูแลกิจกรรมเพิ่มผ่านระบบของเขา

## ทดสอบบน Server

1. ตรวจ Dropdown แสดงบุคคลและกิจกรรมจากฐานข้อมูล และเพิ่มผู้เข้าร่วมพร้อมบทบาท
2. เพิ่มบุคคลเดิม กิจกรรมเดิม บทบาทเดิม ต้องถูกปฏิเสธ; เปลี่ยนบทบาทต้องเพิ่มได้
3. ค้นหาชื่อ กิจกรรม วัด และบทบาท รวมถึงค้นหาไม่พบ; ID เรียงน้อยไปมาก
4. แก้บทบาทหรือกิจกรรม บันทึกและ refresh ต้องไม่ส่งซ้ำ
5. ทดสอบ POST โดยเลือก ID ที่ไม่มีจริง ต้องไม่บันทึก; บทบาทว่างหรือเกิน 100 ตัวอักษรต้องถูกปฏิเสธ
6. ลบผู้เข้าร่วมที่ไม่มี donation อ้างอิงได้; ถ้ามี donation ต้องลบไม่ได้ และเปลี่ยนไปกิจกรรมอื่นไม่ได้
7. ที่หน้า Person ลองลบบุคคลที่มี Party อ้างอิง ต้องมีข้อความปฏิเสธ
8. GET action=delete ต้องตอบ 405; POST ไม่มี CSRF ต้องตอบ 403
9. ทดสอบเปิด Person และเพิ่ม/แก้ไขข้อมูลได้ตามเดิม

ในเครื่องรัน C:\xampp\php\php.exe tests/party.php และ tests/person.php ได้ การทดสอบใช้ SQLite ชั่วคราว ไม่แตะฐานข้อมูลกลาง; SQLite ไม่รองรับ FOR UPDATE จึงถอดคำสั่ง lock เฉพาะใน test adapter การ lock, collation และรหัส error ของ MySQL ต้องทดสอบบน MySQL อีกครั้ง

ระบบอาศัย UNIQUE(person_id,event_id,role) และ Foreign Key แบบห้ามลบข้อมูลที่ถูกอ้างอิงตาม schema ที่ให้มา ตรวจ constraints จริงก่อนใช้งาน ระบบ Donation ในอนาคตต้องตรวจความสัมพันธ์กิจกรรมเมื่อบันทึกเช่นกัน
