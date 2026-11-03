<h2>ทะเบียนบุคคล</h2><a class="button" href="<?= h(personUrl('create')) ?>">+ เพิ่มบุคคล</a>
<form method="get" class="card"><input type="hidden" name="page" value="person"><label for="q">ค้นหาชื่อ เลขประชาชน หรือประเภท</label><input id="q" name="q" value="<?= h($term) ?>"><button>ค้นหา</button><a href="<?= h(personUrl()) ?>">ล้างการค้นหา</a></form>
<p>พบ <?= count($people) ?> รายการ</p>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ชื่อ–นามสกุล</th>
                <th>เลขประชาชน</th>
                <th>ประเภท</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($people as $person): ?><tr>
                    <td><?= h($person['id']) ?></td>
                    <td><?= h($person['first_name'] . ' ' . $person['last_name']) ?></td>
                    <td><?= h($person['citizen_id'] ?? 'ไม่ระบุ') ?></td>
                    <td><?= h($person['person_type']) ?></td>
                    <td><a href="<?= h(personUrl('edit', $person['id'])) ?>">แก้ไข / ลบ</a></td>
                </tr><?php endforeach; ?>
            <?php if (!$people): ?><tr>
                    <td colspan="5">ยังไม่มีข้อมูลที่ตรงกับการค้นหา</td>
                </tr><?php endif; ?></tbody>
    </table>
</div>