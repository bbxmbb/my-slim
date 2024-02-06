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
        $this->tableName = "settingsLog";
    }

    public function createTableIfNotExist($timezone)
    {

        try {
            // Check if the settingstable exists
            $stmt        = $this->pdo->query("SHOW TABLES LIKE '$this->tableName'");
            $tableExists = $stmt->rowCount() > 0;

            // If the table doesn't exist, create it
            if (!$tableExists) {
                //     $createTableQuery = "
                // CREATE TABLE $this->tableName (
                //     id INT PRIMARY KEY AUTO_INCREMENT,
                //     register BOOLEAN  NOT NULL,
                //     register_with_google  BOOLEAN NOT NULL,
                //     login_with_google BOOLEAN NOT NULL,
                //     client_id TEXT NOT NULL,
                //     client_secret TEXT NOT NULL,
                //     created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                //     updated_at timestamp DEFAULT CURRENT_TIMESTAMP
                // )";
                $createTableQuery = "
            CREATE TABLE $this->tableName(
                id INT PRIMARY KEY AUTO_INCREMENT,
                key_name VARCHAR(255) NOT NULL,
                value VARCHAR(255) NOT NULL,
                create_at timestamp DEFAULT CURRENT_TIMESTAMP,
                user VARCHAR(255) NULL
            )";
                $this->pdo->exec($createTableQuery);

                //then insert first data
                // $sql  = "INSERT INTO `$this->tableName` (`register`,`register_with_google`,`login_with_google`) VALUES (?,?,?)";
                // $stmt = $this->pdo->prepare($sql);
                // $stmt->execute([
                //     1,
                //     0,
                //     0
                // ]);

                $sql  = "INSERT INTO `$this->tableName` (`key_name`,`value`) VALUES ('register','1');
                INSERT INTO `$this->tableName` (`key_name`,`value`) VALUES ('register_with_google','0');
                INSERT INTO `$this->tableName` (`key_name`,`value`) VALUES ('login_with_google','0');
                INSERT INTO `$this->tableName` (`key_name`,`value`) VALUES ('client_id','');
                INSERT INTO `$this->tableName` (`key_name`,`value`) VALUES ('client_secret','');";
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
    public function getLastSettings()
    {
        // $settingsModel = new SettingsModel($pdo);
        // $data      = $settingsModel->findAll()->where("id", "in", "(SELECT max(id) FROM `settingsLog` group by key_name)")->execute();

        $this->sql = "SELECT * 
                        FROM $this->tableName 
                        WHERE id in (SELECT max(id) FROM $this->tableName group by `key_name`)";
        try {

            $stmt = $this->pdo->prepare($this->sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {

            $this->lastException = $e;
            error_log($e->getMessage());

            return false;
        }

        $settings = array();

        foreach ($data as $item) {
            // Use the "key_name" as the key in the new associative array
            $settings[$item["key_name"]] = $item["value"];
        }
        return $settings;

    }


}