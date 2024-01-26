<?php

namespace App\Application\Models;

use PDO;
use PDOException;
use App\Application\Handlers\MyResponseHandler;
use Psr\Http\Message\ResponseInterface as Response;

class ItemModel extends Model
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->tableName = "items";
    }

    public function createTableIfNotExist()
    {
        try {
            // Check if the items table exists
            $stmt        = $this->pdo->query("SHOW TABLES LIKE '$this->tableName'");
            $tableExists = $stmt->rowCount() > 0;

            // If the table doesn't exist, create it
            if (!$tableExists) {
                $createTableQuery = "
            CREATE TABLE $this->tableName (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                description VARCHAR(255) NOT NULL,
                numberValue int NOT NULL,
                booleanValue tinyint DEFAULT 0 ,
                arrayValue JSON NOT NULL,
                objectValue JSON NOT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP
            )";
                $this->pdo->exec($createTableQuery);
            }
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function getItems(?int $idFilter, ?string $nameFilter = null, ?string $createdDateFromFilter = null, ?string $createdDateToFilter = null, int $pageSize = 1000, int $pageNumber = 1)
    {
        $offset = ($pageNumber - 1) * $pageSize;
        $sql    = 'SELECT * FROM items';
        $params = [];

        if ($nameFilter || $idFilter || $createdDateFromFilter || $createdDateToFilter) {
            $sql .= ' WHERE';

            if ($idFilter) {
                $sql .= ' id = :id';
                $params['id'] = $idFilter;
            }
            if ($nameFilter) {

                $sql .= ' name LIKE :name';
                $params['name'] = '%' . $nameFilter . '%';
            }
            if ($createdDateFromFilter || $createdDateToFilter) {

                $sql .= ' DATE(created_at) >= :dateFrom and DATE(created_at) <= :dateTo';
                $params['dateFrom'] = $createdDateFromFilter;
                $params['dateTo']   = $createdDateToFilter;
            }
        }

        $sql .= ' LIMIT :limit OFFSET :offset';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

            if ($nameFilter) {
                $stmt->bindParam(':name', $params['name'], PDO::PARAM_STR);
            }
            if ($idFilter) {
                $stmt->bindParam(':id', $params['id'], PDO::PARAM_INT);
            }
            if ($createdDateFromFilter || $createdDateToFilter) {
                $stmt->bindParam(':dateFrom', $params['dateFrom'], PDO::PARAM_STR);
                $stmt->bindParam(':dateTo', $params['dateTo'], PDO::PARAM_STR);
            }

            // var_dump($sql);
            // exit;
            $stmt->execute();

            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw $e;
        }
        return $items;

    }

    public function postItems(array $data)
    {
        $sql = 'INSERT INTO items (name, description,numberValue,booleanValue,arrayValue,objectValue) VALUES (?,
    ?,?,?,?,?)';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['numberValue'],
                $data['booleanValue'] ?? 0,
                json_encode($data['arrayValue'] ?? []),
                json_encode($data['objectValue'] ?? []),
            ]);
        } catch (PDOException $e) {

            throw $e;
        }
    }

    public function putItems(array $data, int $id)
    {
        $sql = 'UPDATE items SET name = ?, description = ?, numberValue = ?, booleanValue= ?, arrayValue= ?, objectValue=
    ? WHERE id = ?';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['numberValue'],
                $data['booleanValue'] ?? 1,
                json_encode($data['arrayValue'] ?? []),
                json_encode($data['objectValue'] ?? []),
                $id
            ]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function deleteItem(int $id)
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM items WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    protected static function checkType(array $items): array
    {
        // Create a response array with different data types
        foreach ($items as &$item) {

            $item['name']         = (string) $item['name'];
            $item['description']  = (string) $item['description'];
            $item['numberValue']  = (float) $item['numberValue'];
            $item['booleanValue'] = (bool) $item['booleanValue'];
            $item['arrayValue']   = json_decode($item['arrayValue'], true);
            $item['objectValue']  = json_decode($item['objectValue']);
        }
        return $items;
    }
}
