<?php
class PartyModel {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }
    public function people() {
        return $this->db->query('SELECT id, first_name, last_name, person_type FROM person ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
    }
    public function events() {
        return $this->db->query('SELECT e.id, e.event_name, t.temple_name FROM event e JOIN temple t ON t.id = e.temple_id ORDER BY e.id ASC')->fetchAll(PDO::FETCH_ASSOC);
    }
    public function search($term) {
        $sql = "SELECT p.id, p.person_id, p.event_id, p.role, n.first_name, n.last_name, e.event_name, t.temple_name FROM party p JOIN person n ON n.id = p.person_id JOIN event e ON e.id = p.event_id JOIN temple t ON t.id = e.temple_id";
        $params = [];
        if ($term !== '') {
            $sql .= " WHERE CONCAT(n.first_name, ' ', n.last_name) LIKE ? ESCAPE '!' OR e.event_name LIKE ? ESCAPE '!' OR t.temple_name LIKE ? ESCAPE '!' OR p.role LIKE ? ESCAPE '!'";
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
            $params = [$like, $like, $like, $like];
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY p.id ASC');
        $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function find($id) {
        $stmt = $this->db->prepare('SELECT id, person_id, event_id, role FROM party WHERE id = ?');
        $stmt->execute([$id]); return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function referencesExist($personId, $eventId) {
        $stmt = $this->db->prepare('SELECT 1 FROM person n CROSS JOIN event e WHERE n.id = ? AND e.id = ?');
        $stmt->execute([$personId, $eventId]); return (bool) $stmt->fetchColumn();
    }
    public function duplicate(array $data, $id) {
        $stmt = $this->db->prepare('SELECT id FROM party WHERE person_id = ? AND event_id = ? AND role = ? AND id <> ?');
        $stmt->execute([$data['person_id'], $data['event_id'], $data['role'], $id ?? 0]); return (bool) $stmt->fetchColumn();
    }
    public function save(array $data, $id = null) {
        $this->db->beginTransaction();
        try {
            if ($id !== null) {
                // Lock the participant before checking related donations (MySQL/InnoDB).
                $stmt = $this->db->prepare('SELECT id FROM party WHERE id = ? FOR UPDATE');
                $stmt->execute([$id]);
                if (!$stmt->fetchColumn()) { throw new DomainException('ไม่พบผู้เข้าร่วม กรุณาโหลดหน้าใหม่'); }
                $stmt = $this->db->prepare('SELECT id FROM donation WHERE party_id = ? AND event_id <> ? LIMIT 1');
                $stmt->execute([$id, $data['event_id']]);
                if ($stmt->fetchColumn()) { throw new DomainException('เปลี่ยนกิจกรรมไม่ได้ เนื่องจากมีเงินบริจาคอ้างอิงกิจกรรมเดิม'); }
            }
            $params = [$data['person_id'], $data['event_id'], $data['role']];
            if ($id === null) { $sql = 'INSERT INTO party (person_id, event_id, role) VALUES (?, ?, ?)'; }
            else { $sql = 'UPDATE party SET person_id = ?, event_id = ?, role = ? WHERE id = ?'; $params[] = $id; }
            $this->db->prepare($sql)->execute($params); $this->db->commit();
        } catch (Throwable $e) { if ($this->db->inTransaction()) { $this->db->rollBack(); } throw $e; }
    }
    public function delete($id) { $this->db->prepare('DELETE FROM party WHERE id = ?')->execute([$id]); }
}
