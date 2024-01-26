<?php

namespace App\Application\Models;

use PDO;
use PDOException;
use App\Application\Handlers\MyResponseHandler;
use Psr\Http\Message\ResponseInterface as Response;

class SettingsModel extends Model
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->tableName = "settings";
    }

    public function createTableIfNotExist($timezone)
    {

        try {
            // Check if the settingstable exists
            $stmt        = $this->pdo->query("SHOW TABLES LIKE '$this->tableName'");
            $tableExists = $stmt->rowCount() > 0;

            // If the table doesn't exist, create it
            if (!$tableExists) {
                $createTableQuery = "
            CREATE TABLE $this->tableName (
                id INT PRIMARY KEY AUTO_INCREMENT,
                register BOOLEAN  NOT NULL,
                register_with_google  BOOLEAN NOT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP
            )";
                $this->pdo->exec($createTableQuery);

                //then insert first data
                $sql  = "INSERT INTO `$this->tableName` (`register`,`register_with_google`) VALUES (?,?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    1,
                    1
                ]);

                //then set timezone 
                $sql  = "SET GLOBAL time_zone = '$timezone';";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
            }

        } catch (PDOException $e) {
            $this->lastException = $e;
            error_log($e->getMessage());

            return false;
            // throw $e;
        }
    }


}