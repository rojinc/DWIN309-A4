<?php
namespace App\Models;

use App\Core\Model;

/**
 * Records financial transactions applied to invoices.
 */
class PaymentModel extends Model
{
    /**
     * Adds a payment entry and returns its identifier.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO payments (invoice_id, amount, payment_date, method, reference, notes, recorded_by, created_at, updated_at)
                VALUES (:invoice_id, :amount, :payment_date, :method, :reference, :notes, :recorded_by, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'invoice_id' => $data['invoice_id'],
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'method' => $data['method'] ?? 'Cash',
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $data['recorded_by'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Returns all payments ordered by date.
     */
    public function all(): array
    {
        $sql = 'SELECT p.*, i.invoice_number FROM payments p INNER JOIN invoices i ON i.id = p.invoice_id ORDER BY p.payment_date DESC';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}