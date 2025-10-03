<?php
namespace App\Models;

use App\Core\Model;

/**
 * Provides persistence operations for invoices and their line items.
 */
class InvoiceModel extends Model
{
    public function all(): array
    {
        $sql = 'SELECT i.*, CONCAT(u.first_name, " ", u.last_name) AS student_name, u.id AS student_user_id, c.title AS course_title
                FROM invoices i
                INNER JOIN enrollments e ON e.id = i.enrollment_id
                INNER JOIN students s ON s.id = e.student_id
                INNER JOIN users u ON u.id = s.user_id
                INNER JOIN courses c ON c.id = e.course_id
                ORDER BY i.issue_date DESC';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT i.*, CONCAT(u.first_name, " ", u.last_name) AS student_name, u.email AS student_email, u.id AS student_user_id, c.title AS course_title
                                     FROM invoices i
                                     INNER JOIN enrollments e ON e.id = i.enrollment_id
                                     INNER JOIN students s ON s.id = e.student_id
                                     INNER JOIN users u ON u.id = s.user_id
                                     INNER JOIN courses c ON c.id = e.course_id
                                     WHERE i.id = :id');
        $stmt->execute(['id' => $id]);
        $invoice = $stmt->fetch();
        if ($invoice === false) {
            return null;
        }
        $invoice['items'] = $this->items($id);
        $invoice['payments'] = $this->payments($id);
        $invoice['balance_due'] = $invoice['total'] - array_sum(array_column($invoice['payments'], 'amount'));
        return $invoice;
    }

    public function findByEnrollment(int $enrollmentId): ?array
    {
        $stmt = $this->db->prepare('SELECT id FROM invoices WHERE enrollment_id = :enrollment_id ORDER BY issue_date DESC LIMIT 1');
        $stmt->execute(['enrollment_id' => $enrollmentId]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        return $this->find((int) $row['id']);
    }

    public function create(array $data, array $items): int
    {
        $sql = 'INSERT INTO invoices (enrollment_id, invoice_number, issue_date, due_date, subtotal, tax_amount, total, status, notes, created_at, updated_at)
                VALUES (:enrollment_id, :invoice_number, :issue_date, :due_date, :subtotal, :tax_amount, :total, :status, :notes, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'enrollment_id' => $data['enrollment_id'],
            'invoice_number' => $data['invoice_number'],
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'subtotal' => $data['subtotal'],
            'tax_amount' => $data['tax_amount'],
            'total' => $data['total'],
            'status' => $data['status'] ?? 'sent',
            'notes' => $data['notes'] ?? null,
        ]);
        $invoiceId = (int) $this->db->lastInsertId();
        $this->syncItems($invoiceId, $items);
        return $invoiceId;
    }

    public function update(int $id, array $data, ?array $items = null): bool
    {
        $sql = 'UPDATE invoices
                SET issue_date = :issue_date,
                    due_date = :due_date,
                    subtotal = :subtotal,
                    tax_amount = :tax_amount,
                    total = :total,
                    status = :status,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'subtotal' => $data['subtotal'],
            'tax_amount' => $data['tax_amount'],
            'total' => $data['total'],
            'status' => $data['status'] ?? 'sent',
            'notes' => $data['notes'] ?? null,
            'id' => $id,
        ]);
        if ($items !== null) {
            $this->syncItems($id, $items);
        }
        return $result;
    }

    public function items(int $invoiceId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM invoice_items WHERE invoice_id = :invoice_id');
        $stmt->execute(['invoice_id' => $invoiceId]);
        return $stmt->fetchAll();
    }

    public function payments(int $invoiceId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM payments WHERE invoice_id = :invoice_id ORDER BY payment_date');
        $stmt->execute(['invoice_id' => $invoiceId]);
        return $stmt->fetchAll();
    }

    private function syncItems(int $invoiceId, array $items): void
    {
        $this->db->prepare('DELETE FROM invoice_items WHERE invoice_id = :invoice_id')->execute(['invoice_id' => $invoiceId]);
        $stmt = $this->db->prepare('INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total)
                                     VALUES (:invoice_id, :description, :quantity, :unit_price, :total)');
        foreach ($items as $item) {
            $stmt->execute([
                'invoice_id' => $invoiceId,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['total'],
            ]);
        }
    }

    public function setStatus(int $invoiceId, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE invoices SET status = :status, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $invoiceId]);
    }

    public function overdue(): array
    {
        $sql = 'SELECT * FROM invoices WHERE due_date < CURDATE() AND status IN ("sent", "partial")';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}