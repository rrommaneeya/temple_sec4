<?php
class DonationModel {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }
    public function events() { return $this->db->query('SELECT e.id, e.event_name, t.temple_name FROM event e JOIN temple t ON t.id=e.temple_id ORDER BY e.id ASC')->fetchAll(PDO::FETCH_ASSOC); }
    public function parties() { return $this->db->query('SELECT p.id, p.event_id, p.role, n.first_name, n.last_name FROM party p JOIN person n ON n.id=p.person_id ORDER BY p.id ASC')->fetchAll(PDO::FETCH_ASSOC); }
    public function find($id) {
        $stmt = $this->db->prepare('SELECT * FROM donation WHERE id=?'); $stmt->execute([$id]); return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function search($term) {
        $sql = "SELECT d.*, e.event_name, t.temple_name, n.first_name, n.last_name, p.role FROM donation d JOIN event e ON e.id=d.event_id JOIN temple t ON t.id=e.temple_id LEFT JOIN party p ON p.id=d.party_id LEFT JOIN person n ON n.id=p.person_id";
        $params = [];
        if ($term !== '') {
            $sql .= " WHERE e.event_name LIKE ? ESCAPE '!' OR t.temple_name LIKE ? ESCAPE '!' OR COALESCE(CONCAT(n.first_name, ' ', n.last_name), 'ไม่ระบุผู้บริจาค') LIKE ? ESCAPE '!' OR d.donation_date LIKE ? ESCAPE '!' OR d.payment_method LIKE ? ESCAPE '!'";
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%'; $params = array_fill(0,5,$like);
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY d.id ASC'); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function save(array $data, $id = null) {
        $this->db->beginTransaction();
        try {
            if ($data['party_id'] !== '') {
                // Coordinate with PartyModel's event update lock.
                $stmt = $this->db->prepare('SELECT event_id FROM party WHERE id=? FOR UPDATE'); $stmt->execute([$data['party_id']]);
                $event = $stmt->fetchColumn();
                if ($event === false || (string) $event !== (string) $data['event_id']) { throw new DomainException('ผู้บริจาคต้องเป็นผู้เข้าร่วมกิจกรรมที่เลือก'); }
            }
            $stmt = $this->db->prepare('SELECT id FROM event WHERE id=?'); $stmt->execute([$data['event_id']]);
            if (!$stmt->fetchColumn()) { throw new DomainException('ไม่พบกิจกรรมที่เลือก'); }
            if ($id !== null && !$this->find($id)) { throw new DomainException('ไม่พบรายการเงินบริจาค กรุณาโหลดหน้าใหม่'); }
            $params = [$data['event_id'], $data['party_id'] === '' ? null : $data['party_id'], $data['amount'], $data['donation_date'], $data['payment_method']];
            if ($id === null) { $sql = 'INSERT INTO donation (event_id,party_id,amount,donation_date,payment_method) VALUES (?,?,?,?,?)'; }
            else { $sql = 'UPDATE donation SET event_id=?,party_id=?,amount=?,donation_date=?,payment_method=? WHERE id=?'; $params[] = $id; }
            $this->db->prepare($sql)->execute($params); $this->db->commit();
        } catch (Throwable $e) { if ($this->db->inTransaction()) { $this->db->rollBack(); } throw $e; }
    }
    public function delete($id) {
        // financial_document has a polymorphic reference, so there is no DB FK.
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id FROM donation WHERE id=? FOR UPDATE'); $stmt->execute([$id]);
            $stmt = $this->db->prepare("SELECT id FROM financial_document WHERE ref_table='donation' AND ref_id=? LIMIT 1"); $stmt->execute([$id]);
            if ($stmt->fetchColumn()) { throw new DomainException('ลบไม่ได้ เนื่องจากมีเอกสารการเงินอ้างอิงรายการนี้'); }
            $this->db->prepare('DELETE FROM donation WHERE id=?')->execute([$id]); $this->db->commit();
        } catch (Throwable $e) { if ($this->db->inTransaction()) { $this->db->rollBack(); } throw $e; }
    }
}
