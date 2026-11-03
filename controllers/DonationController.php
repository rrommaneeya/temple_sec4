<?php
class DonationController {
    private $model;
    public function __construct(DonationModel $model) { $this->model = $model; }
    public static function validate(array $data) {
        $errors = [];
        if (!filter_var($data['event_id'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]])) { $errors[] = 'กรุณาเลือกกิจกรรม'; }
        if ($data['party_id'] !== '' && !filter_var($data['party_id'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]])) { $errors[] = 'ผู้บริจาคไม่ถูกต้อง'; }
        // Validate decimal strings directly; avoid float rounding before DECIMAL storage.
        if (!preg_match('/^(?:0|[1-9][0-9]{0,12})(?:\.[0-9]{1,2})?$/D', $data['amount']) || !preg_match('/[1-9]/', $data['amount'])) { $errors[] = 'จำนวนเงินต้องมากกว่า 0 ไม่เกิน 9,999,999,999,999.99 และมีทศนิยมไม่เกิน 2 ตำแหน่ง'; }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['donation_date']);
        if (!$date || $date->format('Y-m-d') !== $data['donation_date'] || $data['donation_date'] < '1000-01-01') { $errors[] = 'วันที่บริจาคไม่ถูกต้อง'; }
        if (!in_array($data['payment_method'], ['cash','transfer'], true)) { $errors[] = 'กรุณาเลือกเงินสดหรือเงินโอน'; }
        return $errors;
    }
    public function dispatch() {
        $action = $_GET['action'] ?? 'index';
        if (!is_string($action) || !in_array($action,['index','create','edit','delete'],true)) { http_response_code(404); exit('ไม่พบหน้า'); }
        $method = $_SERVER['REQUEST_METHOD'];
        if (!in_array($method,['GET','POST'],true) || ($action === 'delete' && $method !== 'POST') || ($action === 'index' && $method !== 'GET')) { header('Allow: ' . ($action === 'delete' ? 'POST' : 'GET')); http_response_code(405); exit('วิธีส่งข้อมูลไม่ถูกต้อง'); }
        $post = $method === 'POST';
        if ($post && (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'],$_POST['csrf']))) { http_response_code(403); exit('แบบฟอร์มหมดอายุ กรุณาโหลดหน้าใหม่'); }
        if ($action === 'index') {
            $term = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
            $donations = $this->model->search($term); $this->render('index', compact('term','donations')); return;
        }
        $id = null; $data = ['event_id'=>'','party_id'=>'','amount'=>'','donation_date'=>'','payment_method'=>'cash'];
        if ($action !== 'create') {
            $id = filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            $data = $id ? $this->model->find($id) : false;
            if (!$data) { http_response_code(404); exit('ไม่พบรายการเงินบริจาค'); }
            $data['party_id'] = $data['party_id'] ?? '';
        }
        $errors = [];
        if ($post) {
            if ($action !== 'delete') {
                foreach (['event_id','party_id','amount','donation_date','payment_method'] as $field) { $data[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : ''; }
                $errors = self::validate($data);
            }
            if (!$errors) {
                try {
                    if ($action === 'delete') { $this->model->delete($id); } else { $this->model->save($data,$id); }
                    $_SESSION['flash'] = $action === 'delete' ? 'ลบรายการเงินบริจาคแล้ว' : 'บันทึกเงินบริจาคแล้ว';
                    header('Location: ' . donationUrl(), true, 303); return;
                } catch (DomainException $e) { $errors[] = $e->getMessage(); }
                catch (PDOException $e) {
                    $code = (int) ($e->errorInfo[1] ?? 0);
                    if ($code === 1452) { $errors[] = 'ไม่พบกิจกรรมหรือผู้บริจาค กรุณาเลือกใหม่'; }
                    elseif ($code === 1451) { $errors[] = 'ลบไม่ได้ เนื่องจากมีข้อมูลอ้างอิงรายการนี้'; }
                    else { throw $e; }
                }
            }
            http_response_code(422);
        }
        $events = $this->model->events(); $parties = $this->model->parties();
        $this->render('form',compact('action','id','data','errors','events','parties'));
    }
    private function render($view,array $variables) { extract($variables); $section='donation'; require __DIR__ . '/../views/layout.php'; }
}
