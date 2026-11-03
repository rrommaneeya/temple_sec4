<?php
class PersonModel
{
    private $db;
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    public function search($term)
    {
        $sql = 'SELECT id, citizen_id, first_name, last_name, person_type FROM person';
        $params = [];
        if ($term !== '') {
            $sql .= " WHERE CONCAT(first_name, ' ', last_name) LIKE ? ESCAPE '!' OR citizen_id LIKE ? ESCAPE '!' OR person_type LIKE ? ESCAPE '!'";
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
            $params = [$like, $like, $like];
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY id ASC');
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function find($id)
    {
        $stmt = $this->db->prepare('SELECT id, citizen_id, first_name, last_name, person_type FROM person WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function save(array $data, $id = null)
    {
        $values = [$data['citizen_id'] === '' ? null : $data['citizen_id'], $data['first_name'], $data['last_name'], $data['person_type']];
        if ($id === null) {
            $sql = 'INSERT INTO person (citizen_id, first_name, last_name, person_type) VALUES (?, ?, ?, ?)';
        } else {
            $sql = 'UPDATE person SET citizen_id = ?, first_name = ?, last_name = ?, person_type = ? WHERE id = ?';
            $values[] = $id;
        }
        $this->db->prepare($sql)->execute($values);
    }
    public function delete($id)
    {
        $this->db->prepare('DELETE FROM person WHERE id = ?')->execute([$id]);
    }
}
