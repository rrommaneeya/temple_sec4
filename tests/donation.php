<?php
// Reuse isolated SQLite fixtures and its explicitly lock-free test adapter.
require __DIR__ . '/party.php';
require __DIR__ . '/../models/DonationModel.php';
require __DIR__ . '/../controllers/DonationController.php';
$db->sqliteCreateFunction('CONCAT', function (...$values) { return in_array(null, $values, true) ? null : implode('', $values); });
$db->exec('ALTER TABLE donation ADD COLUMN amount DECIMAL(15,2)');
$db->exec('ALTER TABLE donation ADD COLUMN donation_date TEXT');
$db->exec('ALTER TABLE donation ADD COLUMN payment_method TEXT');
$db->exec('CREATE TABLE financial_document(id INTEGER PRIMARY KEY, ref_table TEXT, ref_id INTEGER)');
$model = new DonationModel($db);
$data = ['event_id'=>'1','party_id'=>'','amount'=>'0.01','donation_date'=>'2026-11-03','payment_method'=>'cash'];
checkParty(!DonationController::validate($data), 'valid donation');
foreach (['0','-1','1.001','1e3','10000000000000','abc','0.00'] as $amount) {
    $bad=$data; $bad['amount']=$amount; checkParty((bool)DonationController::validate($bad),'reject amount '.$amount);
}
$max=$data; $max['amount']='9999999999999.99'; checkParty(!DonationController::validate($max),'DECIMAL maximum accepted');
foreach (['2026-02-30','2026-2-1','0000-01-01'] as $date) { $bad=$data; $bad['donation_date']=$date; checkParty((bool)DonationController::validate($bad),'reject date '.$date); }
$bad=$data; $bad['payment_method']='card'; checkParty((bool)DonationController::validate($bad),'reject payment');
$model->save($data); checkParty($model->find(2)['party_id']===null,'anonymous saved as NULL');
$data['party_id']='1'; $data['amount']='250.50'; $model->save($data);
checkParty((int)$model->find(3)['party_id']===1,'named donation');
checkParty(count($model->search('สมชาย'))===2,'donor search');
checkParty(count($model->search('ไม่ระบุผู้บริจาค'))===1,'anonymous search');
checkParty(!$model->search("' OR 1=1 --") && !$model->search('%'),'donation search injection and wildcard');
$data['event_id']='2';
try { $model->save($data,3); throw new RuntimeException('cross-event accepted'); } catch (DomainException $e) { echo "PASS: cross-event donor rejected\n"; }
checkParty((int)$model->find(3)['event_id']===1 && !$db->inTransaction(),'failed donation update rollback');
$data['party_id']=''; $data['payment_method']='transfer'; $model->save($data,3);
checkParty($model->find(3)['payment_method']==='transfer' && (int)$model->find(3)['event_id']===2,'edit anonymous event and payment');
$data['event_id']='999';
try { $model->save($data); throw new RuntimeException('missing event accepted'); } catch (DomainException $e) { echo "PASS: missing event rejected\n"; }
$db->exec("INSERT INTO financial_document VALUES(1,'donation',3)");
try { $model->delete(3); throw new RuntimeException('document donation deleted'); } catch (DomainException $e) { echo "PASS: document reference protected\n"; }
$model->delete(2); checkParty(!$model->find(2),'delete donation');
echo "Donation SQLite checks passed. MySQL and hosted UI remain unverified.\n";
