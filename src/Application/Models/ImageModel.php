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

}