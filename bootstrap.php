<?php
ini_set('display_errors', '0');
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/PersonModel.php';
require_once __DIR__ . '/controllers/PersonController.php';
require_once __DIR__ . '/models/PartyModel.php';
require_once __DIR__ . '/controllers/PartyController.php';
require_once __DIR__ . '/models/DonationModel.php';
require_once __DIR__ . '/controllers/DonationController.php';
if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
try {
    $page = $_GET['page'] ?? 'person';
    if (!is_string($page) || !in_array($page, ['person', 'party', 'donation'], true)) { http_response_code(404); exit('ไม่พบหน้า'); }
    $db = getDBConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    if ($page === 'party') { (new PartyController(new PartyModel($db)))->dispatch(); }
    elseif ($page === 'donation') { (new DonationController(new DonationModel($db)))->dispatch(); }
    else { (new PersonController(new PersonModel($db)))->dispatch(); }
} catch (Throwable $e) {
    error_log('sec4 request failed: ' . get_class($e));
    http_response_code(503);
    echo '<!doctype html><html lang="th"><meta charset="utf-8"><title>ระบบไม่พร้อมใช้งาน</title><p>ระบบไม่พร้อมใช้งานชั่วคราว กรุณาติดต่อผู้ดูแลระบบ</p></html>';
}
