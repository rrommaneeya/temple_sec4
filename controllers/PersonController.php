<?php
class PersonController
{
    private $model;
    public function __construct(PersonModel $model)
    {
        $this->model = $model;
    }
    public static function validate(array $data)
    {
        $errors = [];
        foreach (['first_name' => 100, 'last_name' => 100, 'person_type' => 50] as $field => $max) {
            if ($data[$field] === '' || textLength($data[$field]) > $max) {
                $errors[] = 'กรุณากรอกชื่อ นามสกุล และประเภทบุคคลให้ครบและไม่เกินความยาวที่กำหนด';
                break;
            }
        }
        if ($data['citizen_id'] !== '' && !preg_match('/^[0-9]{13}$/D', $data['citizen_id'])) {
            $errors[] = 'เลขประจำตัวประชาชนต้องเป็นตัวเลข 13 หลัก หรือเว้นว่างหากไม่ทราบ';
        }
        return $errors;
    }
    public function dispatch()
    {
        $action = $_GET['action'] ?? 'index';
        if (!is_string($action) || !in_array($action, ['index', 'create', 'edit', 'delete'], true)) {
            http_response_code(404);
            exit('ไม่พบหน้า');
        }
        $post = $_SERVER['REQUEST_METHOD'] === 'POST';
        if ($post && (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf']))) {
            http_response_code(403);
            exit('แบบฟอร์มหมดอายุ กรุณาโหลดหน้าใหม่');
        }
        if ($action === 'delete' && !$post) {
            header('Allow: POST');
            http_response_code(405);
            exit('ต้องลบผ่านแบบฟอร์ม');
        }
        if ($action === 'index') {
            $term = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
            $people = $this->model->search($term);
            $this->render('index', compact('people', 'term'));
            return;
        }
        $id = null;
        $data = ['citizen_id' => '', 'first_name' => '', 'last_name' => '', 'person_type' => ''];
        if ($action !== 'create') {
            $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $data = $id ? $this->model->find($id) : false;
            if (!$data) {
                http_response_code(404);
                exit('ไม่พบข้อมูลบุคคล');
            }
        }
        $errors = [];
        if ($post) {
            if ($action !== 'delete') {
                foreach (['citizen_id', 'first_name', 'last_name', 'person_type'] as $field) {
                    $data[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
                }
                $errors = self::validate($data);
            }
            if (!$errors) {
                try {
                    if ($action === 'delete') {
                        $this->model->delete($id);
                    } else {
                        $this->model->save($data, $id);
                    }
                    $_SESSION['flash'] = $action === 'delete' ? 'ลบข้อมูลแล้ว' : 'บันทึกข้อมูลแล้ว';
                    header('Location: ' . personUrl(), true, 303);
                    return;
                } catch (PDOException $e) {
                    $code = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
                    if ($code === 1062) {
                        $errors[] = 'เลขประจำตัวประชาชนนี้มีอยู่แล้ว';
                    } elseif ($code === 1451) {
                        $errors[] = 'ลบไม่ได้ เนื่องจากบุคคลนี้มีข้อมูลอ้างอิง เช่น ผู้เข้าร่วมกิจกรรมหรือรายการการเงิน';
                    } else {
                        throw $e;
                    }
                }
            }
            http_response_code(422);
        }
        $this->render('form', compact('action', 'id', 'data', 'errors'));
    }
    private function render($view, array $variables)
    {
        extract($variables);
        require __DIR__ . '/../views/layout.php';
    }
}
