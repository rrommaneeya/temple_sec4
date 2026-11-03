<?php $section = $section ?? 'person';
$title = ['person' => 'ระบบจัดการบุคคล', 'party' => 'ระบบผู้เข้าร่วมกิจกรรม', 'donation' => 'ระบบจัดการเงินบริจาค'][$section]; ?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · Section 4</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header>
        <p>GROUP 11 · SECTION 4</p>
        <h1><?= h($title) ?></h1>
        <p>ระบบติดตามกิจกรรมทางการเงินของวัด</p>
        <nav aria-label="เมนูหลัก">
            <a class="button" href="https://bservcpe.eng.kps.ku.ac.th/db26/db26_080/g11/">หน้าหลัก</a>
            <a class="button" href="<?= h(personUrl()) ?>">ทะเบียนบุคคล</a>
            <a class="button" href="<?= h(partyUrl()) ?>">ผู้เข้าร่วมกิจกรรม</a>
            <a class="button" href="<?= h(donationUrl()) ?>">เงินบริจาค</a>
        </nav>
    </header>
    <main>
        <?php if (isset($_SESSION['flash'])): ?><p class="notice" role="status"><?= h($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']);
                                                                                                            endif; ?>
        <?php require __DIR__ . '/' . $section . '/' . $view . '.php'; ?>
    </main>
</body>

</html>