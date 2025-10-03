<?php
namespace App\Core;

use PDO;

/**
 * Base model providing database connectivity to child DAOs.
 */
abstract class Model
{
    protected PDO $db;

    /**
     * Supplies DAO implementations with a shared PDO handle.
     */
    public function __construct()
    {
        $this->db = Database::connection();
    }
}