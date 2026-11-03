# Donation CRUD

## โครงสร้างและไฟล์สำหรับอัปโหลด

อัปโหลด 8 ไฟล์ต่อไปนี้ไปยัง sec4 โดยรักษาโครงสร้างและสำรองไฟล์เดิมก่อน:

- bootstrap.php — เพิ่ม router page=donation
- helpers.php — เพิ่ม donationUrl()
- views/layout.php — เพิ่มชื่อหน้าและเมนูเงินบริจาค
- models/DonationModel.php — SQL CRUD/Search และตรวจ event/party ใน transaction
- controllers/DonationController.php — ตรวจข้อมูล, CSRF, HTTP method และ redirect 303 หลังบันทึก
- views/donation/index.php — ตารางและค้นหา เรียง ID น้อยไปมาก
- views/donation/form.php — เพิ่ม/แก้ไข/ยืนยันลบ
- assets/donation.js — กรอง Dropdown ผู้บริจาคตามกิจกรรม

Controller รับ request ตรวจข้อมูลแล้วเรียก Model; Model ใช้ PDO prepared statements; View ใช้ h() escape ข้อมูลก่อนแสดง ไม่มีการแก้ schema หรือ config/database.php ไม่ต้องอัปโหลด tests หรือเอกสารนี้

เปิด index.php?page=donation หากยังไม่มีกิจกรรม เปิดดูหน้าได้แต่บันทึกไม่ได้ ต้องให้ผู้ดูแลกิจกรรมเพิ่ม event และ temple ก่อน หากไม่ระบุผู้บริจาค ไม่ต้องมี party

## กฎข้อมูล

- amount > 0 สูงสุด 9999999999999.99 ทศนิยมไม่เกิน 2 ตำแหน่ง ตรวจเป็น string เพื่อไม่ปัดเศษผ่าน float
- วันที่ต้องเป็น YYYY-MM-DD และวันที่มีอยู่จริง (ค.ศ.)
- payment_method เฉพาะ cash หรือ transfer
- party_id ว่างเก็บ NULL; ถ้ามีต้องอยู่ใน event_id ที่เลือก ตรวจใน transaction พร้อม lock Party ให้ทำงานร่วมกับการเปลี่ยนกิจกรรมใน PartyModel
- ลบด้วย POST และ CSRF เท่านั้น มีหน้าให้ยืนยันก่อนลบ
- ปฏิเสธการลบรายการที่ financial_document อ้างอิงอยู่ การเพิ่มเอกสารจากโมดูลอื่นควร lock แถว donation ด้วยเช่นกัน เพราะ ref_table/ref_id ไม่มี FK จึงไม่สามารถรับประกัน race กับโมดูลที่ไม่ใช้ lock ร่วมกัน
- PRG ป้องกันการ refresh หลังบันทึกสำเร็จแล้วเพิ่มซ้ำ แต่รายการที่มีรายละเอียดเหมือนกันสามารถเป็นการบริจาคคนละครั้งได้ จึงไม่เพิ่ม UNIQUE บนรายละเอียดการบริจาค

## ทดสอบบน Server

1. เพิ่มแบบไม่ระบุผู้บริจาค ตรวจในฐานข้อมูลว่า party_id เป็น NULL
2. เพิ่มแบบระบุผู้บริจาค Dropdown ต้องมีเฉพาะ Party ของกิจกรรมที่เลือก เปลี่ยนกิจกรรมแล้วต้องล้างผู้บริจาคเดิมที่ไม่สัมพันธ์กัน
3. ทดสอบ POST ที่แก้ party_id เป็นคนละกิจกรรม ต้องไม่บันทึก
4. ทดสอบจำนวนเงิน 0, ติดลบ, ทศนิยมเกิน 2 หลัก, เกินขอบเขต และวันที่ผิด ต้องถูกปฏิเสธ
5. ค้นหาชื่อ กิจกรรม วัด วันที่ cash/transfer และคำว่า ไม่ระบุผู้บริจาค
6. แก้ไขจำนวนเงิน วันที่ วิธีชำระและผู้บริจาค แล้ว refresh ต้องไม่เพิ่มรายการ
7. ลบรายการทดสอบที่ไม่มีเอกสารอ้างอิงได้; รายการที่มีเอกสารต้องถูกปฏิเสธ
8. POST ไม่มี CSRF ต้องตอบ 403 และ GET action=delete ต้องตอบ 405
9. กลับหน้า Party ทดสอบลบ Party ที่มี donation และเปลี่ยนกิจกรรม ต้องถูกปฏิเสธ

รัน C:\xampp\php\php.exe tests/donation.php ในเครื่อง การทดสอบใช้ SQLite ในหน่วยความจำเท่านั้น โดยถอด FOR UPDATE เฉพาะ test adapter ไม่ได้ยืนยัน MySQL locking, collation หรือ HTTP บน hosting และไม่ได้แก้ฐานข้อมูลกลาง

Dashboard เป็นขั้นถัดไป ยังไม่เพิ่มในรอบนี้
