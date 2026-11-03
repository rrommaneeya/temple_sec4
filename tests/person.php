<?php
require __DIR__ . '/../helpers.php';
require __DIR__ . '/../models/PersonModel.php';
require __DIR__ . '/../controllers/PersonController.php';
function check($condition, $label) { if (!$condition) { throw new RuntimeException($label); } echo "PASS: $label\n"; }
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->sqliteCreateFunction('CONCAT', function (...$values) { return implode('', $values); });
$db->exec('PRAGMA foreign_keys = ON');
$db->exec('CREATE TABLE person (id INTEGER PRIMARY KEY, citizen_id VARCHAR(13) UNIQUE, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, person_type VARCHAR(50) NOT NULL)');
$db->exec('CREATE TABLE party (id INTEGER PRIMARY KEY, person_id INTEGER REFERENCES person(id))');
$model = new PersonModel($db);
$data = ['citizen_id' => '', 'first_name' => 'ทดสอบ', 'last_name' => 'ระบบ', 'person_type' => 'บุคคลทั่วไป'];
check(PersonController::validate($data) === [], 'valid Thai name and anonymous ID');
$invalid = $data; $invalid['first_name'] = str_repeat('ก', 101);
check(count(PersonController::validate($invalid)) > 0, 'Thai character length enforced');
$invalid = $data; $invalid['citizen_id'] = '123';
check(count(PersonController::validate($invalid)) > 0, 'invalid citizen ID rejected');
$model->save($data); $model->save($data);
check(count($model->search('')) === 2, 'create and multiple NULL IDs');
check($model->find(1)['citizen_id'] === null, 'blank ID stored as NULL');
check(count($model->search('ทดสอบ')) === 2, 'Thai name search');
check(count($model->search("' OR 1=1 --")) === 0, 'SQL injection search treated as data');
check(count($model->search('%')) === 0, 'wildcard search escaped');
$data['citizen_id'] = '1234567890123'; $model->save($data, 1);
check($model->find(1)['citizen_id'] === $data['citizen_id'], 'update');
try { $model->save($data); check(false, 'duplicate ID rejected'); } catch (PDOException $e) { echo "PASS: duplicate ID rejected\n"; }
$db->exec('INSERT INTO party (person_id) VALUES (1)');
try { $model->delete(1); check(false, 'dependent person protected'); } catch (PDOException $e) { echo "PASS: dependent person protected\n"; }
$model->delete(2); check(!$model->find(2), 'delete unreferenced person');
check(h('<script>') === '&lt;script&gt;', 'HTML escaped');
echo "SQLite checks only; MySQL integration and hosting verification remain required.\n";
