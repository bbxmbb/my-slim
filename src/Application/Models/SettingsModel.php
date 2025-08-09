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