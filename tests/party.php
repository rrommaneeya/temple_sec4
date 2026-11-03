<?php
require __DIR__ . '/../helpers.php';
require __DIR__ . '/../models/PartyModel.php';
require __DIR__ . '/../controllers/PartyController.php';
// SQLite does not support row locks. This adapter tests business behavior only.
class PartyTestPDO extends PDO {
    public function prepare($query, $options = []) { return parent::prepare(str_replace(' FOR UPDATE', '', $query), $options); }
}
function checkParty($ok, $label) { if (!$ok) { throw new RuntimeException($label); } echo "PASS: $label\n"; }
$db = new PartyTestPDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->sqliteCreateFunction('CONCAT', function (...$values) { return implode('', $values); });
$db->exec('PRAGMA foreign_keys = ON');
$db->exec('CREATE TABLE person(id INTEGER PRIMARY KEY, first_name TEXT, last_name TEXT, person_type TEXT)');
$db->exec('CREATE TABLE temple(id INTEGER PRIMARY KEY, temple_name TEXT)');
$db->exec('CREATE TABLE event(id INTEGER PRIMARY KEY, temple_id INTEGER REFERENCES temple(id), event_name TEXT)');
$db->exec('CREATE TABLE party(id INTEGER PRIMARY KEY, person_id INTEGER NOT NULL REFERENCES person(id), event_id INTEGER NOT NULL REFERENCES event(id), role TEXT NOT NULL, UNIQUE(person_id,event_id,role))');
$db->exec('CREATE TABLE donation(id INTEGER PRIMARY KEY, party_id INTEGER REFERENCES party(id), event_id INTEGER REFERENCES event(id))');
$db->exec("INSERT INTO person VALUES(1,'สมชาย','ใจดี','บุคคลทั่วไป'),(2,'ทดสอบ','ระบบ','เจ้าหน้าที่')");
$db->exec("INSERT INTO temple VALUES(1,'วัดทดสอบ')");
$db->exec("INSERT INTO event VALUES(1,1,'กฐิน'),(2,1,'ผ้าป่า')");
$model = new PartyModel($db);
$data = ['person_id' => '1', 'event_id' => '1', 'role' => 'ผู้บริจาค'];
checkParty(PartyController::validate($data) === [], 'valid participant');
foreach (['person_id' => '0', 'event_id' => 'abc', 'role' => str_repeat('ก',101)] as $field => $value) { $bad = $data; $bad[$field] = $value; checkParty((bool) PartyController::validate($bad), 'invalid ' . $field); }
checkParty(count($model->people()) === 2 && count($model->events()) === 2, 'dropdown database options');
checkParty(!$model->referencesExist(999,1), 'missing foreign key detected');
$model->save($data); checkParty($model->find(1)['role'] === 'ผู้บริจาค', 'create and read');
checkParty($model->duplicate($data,null) && !$model->duplicate($data,1), 'duplicate detection excludes current record');
try { $model->save($data); throw new RuntimeException('duplicate accepted'); } catch (PDOException $e) { echo "PASS: unique constraint\n"; }
$data['role'] = 'ผู้จัดงาน'; $model->save($data);
checkParty(array_map('intval', array_column($model->search(''), 'id')) === [1,2], 'multiple roles and ascending IDs');
foreach (['สมชาย', 'กฐิน', 'วัดทดสอบ', 'ผู้จัดงาน'] as $term) { checkParty(count($model->search($term)) > 0, 'search ' . $term); }
checkParty(!$model->search("' OR 1=1 --") && !$model->search('%'), 'search injection and wildcards');
$data['event_id'] = '2'; $model->save($data,2);
checkParty((int) $model->find(2)['event_id'] === 2, 'edit event without donations');
$db->exec('INSERT INTO donation VALUES(1,1,1)');
$data['role'] = 'ผู้บริจาค';
try { $model->save($data,1); throw new RuntimeException('event change accepted'); } catch (DomainException $e) { echo "PASS: donation event consistency\n"; }
checkParty((int) $model->find(1)['event_id'] === 1 && !$db->inTransaction(), 'failed edit rolled back');
try { $model->delete(1); throw new RuntimeException('referenced party deleted'); } catch (PDOException $e) { echo "PASS: donation delete dependency\n"; }
try { $db->exec('DELETE FROM person WHERE id=1'); throw new RuntimeException('referenced person deleted'); } catch (PDOException $e) { echo "PASS: person delete dependency\n"; }
$model->delete(2); checkParty(!$model->find(2), 'delete unreferenced participant');
echo "SQLite business checks only; MySQL locking, collation, error codes and hosted HTTP checks still required.\n";
