<?php

namespace App\Application\Models;

use PDO;
use PDOException;
use App\Application\Handlers\MyResponseHandler;
use Psr\Http\Message\ResponseInterface as Response;

class ImageModel extends Model
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->tableName = "image";

    }

    public function createTableIfNotExist()
    {
        try {
            // Check if the users table exists
            $stmt        = $this->pdo->query("SHOW TABLES LIKE '$this->tableName'");
            $tableExists = $stmt->rowCount() > 0;

            // If the table doesn't exist, create it
            if (!$tableExists) {
                $createTableQuery = "
            CREATE TABLE $this->tableName (
                id INT PRIMARY KEY AUTO_INCREMENT,
                filename VARCHAR(255) NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                table_name VARCHAR(255) NOT NULL,
                table_id INT NOT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                created_by VARCHAR(255) NOT NULL,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_by VARCHAR(255) NOT NULL
            )";
                $this->pdo->exec($createTableQuery);
            }
        } catch (PDOException $e) {
            $this->lastException = $e;
            error_log($e->getMessage());

            return false;
            // throw $e;
        }
    }

}