<?php
class PartyController {
    private $model;
    public function __construct(PartyModel $model) { $this->model = $model; }
    public static function validate(array $data) {
        $errors = [];
        foreach (['person_id', 'event_id'] as $field) {
            if (!filter_var($data[$field], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) { $errors[] = 'กรุณาเลือกบุคคลและกิจกรรมให้ถูกต้อง'; break; }
        }
        if ($data['role'] === '' || textLength($data['role']) > 100) { $errors[] = 'กรุณาระบุบทบาทไม่เกิน 100 ตัวอักษร'; }
        return $errors;
    }
    public function dispatch() {
        $action = $_GET['action'] ?? 'index';
        if (!is_string($action) || !in_array($action, ['index', 'create', 'edit', 'delete'], true)) { http_response_code(404); exit('ไม่พบหน้า'); }
        $method = $_SERVER['REQUEST_METHOD'];
        if (!in_array($method, ['GET', 'POST'], true) || ($action === 'delete' && $method !== 'POST') || ($action === 'index' && $method !== 'GET')) { header('Allow: ' . ($action === 'delete' ? 'POST' : 'GET')); http_response_code(405); exit('วิธีส่งข้อมูลไม่ถูกต้อง'); }
        $post = $method === 'POST';
        if ($post && (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf']))) { http_response_code(403); exit('แบบฟอร์มหมดอายุ กรุณาโหลดหน้าใหม่'); }
        if ($action === 'index') {
            $term = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
            $parties = $this->model->search($term); $this->render('index', compact('parties', 'term')); return;
        }
        $id = null; $data = ['person_id' => '', 'event_id' => '', 'role' => ''];
        if ($action !== 'create') {
            $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $data = $id ? $this->model->find($id) : false;
            if (!$data) { http_response_code(404); exit('ไม่พบผู้เข้าร่วมกิจกรรม'); }
        }
        $errors = [];
        if ($post) {
            if ($action !== 'delete') {
                foreach (['person_id', 'event_id', 'role'] as $field) { $data[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : ''; }
                $errors = self::validate($data);
                if (!$errors && !$this->model->referencesExist($data['person_id'], $data['event_id'])) { $errors[] = 'ไม่พบบุคคลหรือกิจกรรมที่เลือก กรุณาเลือกใหม่'; }
                if (!$errors && $this->model->duplicate($data, $id)) { $errors[] = 'บุคคลนี้มีบทบาทนี้ในกิจกรรมที่เลือกแล้ว'; }
            }
            if (!$errors) {
                try {
                    if ($action === 'delete') { $this->model->delete($id); } else { $this->model->save($data, $id); }
                    $_SESSION['flash'] = $action === 'delete' ? 'ลบผู้เข้าร่วมแล้ว' : 'บันทึกผู้เข้าร่วมแล้ว';
                    header('Location: ' . partyUrl(), true, 303); return;
                } catch (DomainException $e) { $errors[] = $e->getMessage(); }
                catch (PDOException $e) {
                    $code = (int) ($e->errorInfo[1] ?? 0);
                    if ($code === 1062) { $errors[] = 'บุคคลนี้มีบทบาทนี้ในกิจกรรมที่เลือกแล้ว'; }
                    elseif ($code === 1451) { $errors[] = 'ลบไม่ได้ เนื่องจากมีเงินบริจาคอ้างอิงผู้เข้าร่วมนี้'; }
                    elseif ($code === 1452) { $errors[] = 'บุคคลหรือกิจกรรมถูกลบไปแล้ว กรุณาเลือกใหม่'; }
                    else { throw $e; }
                }
            }
            http_response_code(422);
        }
        $people = $this->model->people(); $events = $this->model->events();
        $this->render('form', compact('action', 'id', 'data', 'errors', 'people', 'events'));
    }
    private function render($view, array $variables) { extract($variables); $section = 'party'; require __DIR__ . '/../views/layout.php'; }
}
