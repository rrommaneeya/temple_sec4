<h2><?= $action === 'create' ? 'เพิ่มบุคคล' : 'แก้ไขข้อมูลบุคคล' ?></h2>
<?php foreach ($errors as $error): ?><p class="error" role="alert"><?= h($error) ?></p><?php endforeach; ?>
<form method="post" action="<?= h(personUrl($action === 'create' ? 'create' : 'edit', $id)) ?>" class="card">
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
    <label for="citizen_id">เลขประจำตัวประชาชน (เว้นว่างได้)</label><input id="citizen_id" name="citizen_id" inputmode="numeric" pattern="[0-9]{13}" maxlength="13" value="<?= h($data['citizen_id']) ?>">
    <label for="first_name">ชื่อ *</label><input id="first_name" name="first_name" maxlength="100" required value="<?= h($data['first_name']) ?>">
    <label for="last_name">นามสกุล *</label><input id="last_name" name="last_name" maxlength="100" required value="<?= h($data['last_name']) ?>">
    <label for="person_type">ประเภทบุคคล *</label><input id="person_type" name="person_type" list="types" maxlength="50" required value="<?= h($data['person_type']) ?>"><datalist id="types">
        <option value="พระ">
        <option value="บุคคลทั่วไป">
        <option value="เจ้าหน้าที่">
    </datalist>
    <button>บันทึกข้อมูล</button> <a href="<?= h(personUrl()) ?>">กลับทะเบียนบุคคล</a>
</form>
<?php if ($id): ?><section class="card">
        <h2>ลบข้อมูลบุคคล</h2>
        <p>ระบบจะไม่ลบบุคคลที่มีข้อมูลอ้างอิง กรุณาตรวจสอบชื่อก่อนยืนยัน</p>
        <form method="post" action="<?= h(personUrl('delete', $id)) ?>"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><button class="delete">ยืนยันลบ <?= h($data['first_name'] . ' ' . $data['last_name']) ?></button></form>
    </section><?php endif; ?>