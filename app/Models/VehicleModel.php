<?php
namespace App\Models;

use App\Core\Model;

/**
 * Data gateway for fleet vehicles including service history.
 */
class VehicleModel extends Model
{
    /**
     * Lists all vehicles with branch and utilisation stats.
     */
    public function all(): array
    {
        $sql = 'SELECT v.*, b.name AS branch_name,
                       (SELECT COUNT(*) FROM schedules s WHERE s.vehicle_id = v.id AND s.scheduled_date >= CURDATE()) AS upcoming_assignments
                FROM vehicles v
                LEFT JOIN branches b ON b.id = v.branch_id
                ORDER BY v.name';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a single vehicle.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Creates a vehicle record.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO vehicles (name, type, transmission, plate_number, vin, branch_id, status, last_service_date, next_service_due, notes, created_at, updated_at)
                VALUES (:name, :type, :transmission, :plate_number, :vin, :branch_id, :status, :last_service_date, :next_service_due, :notes, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'transmission' => $data['transmission'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'vin' => $data['vin'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'status' => $data['status'] ?? 'available',
            'last_service_date' => $data['last_service_date'] ?? null,
            'next_service_due' => $data['next_service_due'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Updates vehicle details and availability state.
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE vehicles
                SET name = :name,
                    type = :type,
                    transmission = :transmission,
                    plate_number = :plate_number,
                    vin = :vin,
                    branch_id = :branch_id,
                    status = :status,
                    last_service_date = :last_service_date,
                    next_service_due = :next_service_due,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'transmission' => $data['transmission'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'vin' => $data['vin'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'status' => $data['status'] ?? 'available',
            'last_service_date' => $data['last_service_date'] ?? null,
            'next_service_due' => $data['next_service_due'] ?? null,
            'notes' => $data['notes'] ?? null,
            'id' => $id,
        ]);
    }
}