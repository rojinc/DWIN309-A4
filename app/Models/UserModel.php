<?php
namespace App\Models;

use App\Core\Model;
use PDOException;

/**
 * Data access layer for user accounts and shared profile information.
 */
class UserModel extends Model
{
    /**
     * Retrieves all users ordered by creation date with optional role filter.
     */
    public function all(?string $role = null): array
    {
        if ($role) {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE role = :role ORDER BY created_at DESC');
            $stmt->execute(['role' => $role]);
            return $stmt->fetchAll();
        }
        $stmt = $this->db->query('SELECT * FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    /**
     * Locates a user by their primary key.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user === false ? null : $user;
    }

    /**
     * Finds a user via email address for authentication workflows.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user === false ? null : $user;
    }

    /**
     * Persists a new user record and returns the inserted identifier.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO users (role, first_name, last_name, email, phone, password_hash, status, branch_id, created_at, updated_at)
                VALUES (:role, :first_name, :last_name, :email, :phone, :password_hash, :status, :branch_id, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'role' => $data['role'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password_hash' => $data['password_hash'],
            'status' => $data['status'] ?? 'active',
            'branch_id' => $data['branch_id'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Updates an existing user record.
     */
    public function update(int $id, array $data): bool
    {
        $fields = ['role', 'first_name', 'last_name', 'email', 'phone', 'status', 'branch_id'];
        $setParts = [];
        $params = ['id' => $id];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $setParts[] = $field . ' = :' . $field;
                $params[$field] = $data[$field];
            }
        }
        if (isset($data['password_hash'])) {
            $setParts[] = 'password_hash = :password_hash';
            $params['password_hash'] = $data['password_hash'];
        }
        if (empty($setParts)) {
            return true;
        }
        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Soft deletes a user by toggling status to archived.
     */
    public function archive(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET status = "archived", updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Performs a basic search across key user attributes.
     */
    public function search(string $term, ?string $role = null): array
    {
        $pattern = '%' . $term . '%';
        if ($role) {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE role = :role AND (first_name LIKE :term OR last_name LIKE :term OR email LIKE :term) ORDER BY first_name');
            $stmt->execute(['role' => $role, 'term' => $pattern]);
        } else {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE first_name LIKE :term OR last_name LIKE :term OR email LIKE :term ORDER BY first_name');
            $stmt->execute(['term' => $pattern]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Counts users grouped by role for dashboard statistics.
     */
    public function countsByRole(): array
    {
        $stmt = $this->db->query('SELECT role, COUNT(*) as total FROM users GROUP BY role');
        $result = $stmt->fetchAll();
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['role']] = (int) $row['total'];
        }
        return $counts;
    }
}