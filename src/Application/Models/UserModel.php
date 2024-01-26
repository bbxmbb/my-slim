<?php

namespace App\Application\Models;

use PDO;
use PDOException;
use App\Application\Handlers\MyResponseHandler;
use Psr\Http\Message\ResponseInterface as Response;

class UserModel extends Model
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->tableName = "users";

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
                email VARCHAR(255) UNIQUE NOT NULL,
                google_sub_id  VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                confirmation_code text NOT NULL,
                confirmed tinyint DEFAULT 0,
                reset_password_code text NOT NULL,
                user_role SMALLINT NOT NULL DEFAULT '99',
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP
            )";
                $this->pdo->exec($createTableQuery);
            }
        } catch (PDOException $e) {
            throw $e;
        }
    }

}