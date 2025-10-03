<?php
namespace App\Models;

use App\Core\Model;

/**
 * CRUD interface for driving school branches.
 */
class BranchModel extends Model
{
    /**
     * Fetches all branches sorted alphabetically.
     */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM branches ORDER BY name');
        return $stmt->fetchAll();
    }

    /**
     * Returns a branch by its ID.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM branches WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Inserts a new branch record.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO branches (name, address, city, state, postcode, phone, email, manager_name, opening_hours, created_at, updated_at)
                VALUES (:name, :address, :city, :state, :postcode, :phone, :email, :manager_name, :opening_hours, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postcode' => $data['postcode'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'manager_name' => $data['manager_name'] ?? null,
            'opening_hours' => $data['opening_hours'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Updates an existing branch record.
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE branches
                SET name = :name,
                    address = :address,
                    city = :city,
                    state = :state,
                    postcode = :postcode,
                    phone = :phone,
                    email = :email,
                    manager_name = :manager_name,
                    opening_hours = :opening_hours,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postcode' => $data['postcode'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'manager_name' => $data['manager_name'] ?? null,
            'opening_hours' => $data['opening_hours'] ?? null,
            'id' => $id,
        ]);
    }
}