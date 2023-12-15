<?php
namespace App\Application\Controllers;

use PDO;
use PDOException;
use Slim\Routing\RouteContext;
use Respect\Validation\Validator as v;
use App\Application\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Exceptions\NestedValidationException;

class ItemController extends Controller
{
    public function getItem(Request $request, Response $response)
    {

        $pdo = $this->container->get(PDO::class);
        // $token = $request->getAttribute('jwt_token');
        // return $response->withJson(['username' => $token->username]);
        $queryParams = $request->getQueryParams();
        $nameFilter  = isset($queryParams['name']) ? $queryParams['name'] : null;
        $idFilter    = isset($queryParams['id']) ? $queryParams['id'] : null;
        $pageSize    = isset($queryParams['pageSize']) ? (int) $queryParams['pageSize'] : 1000; // Default page size
        $pageNumber  = isset($queryParams['pageNumber']) ? (int) $queryParams['pageNumber'] : 1; // Default page number

        // Calculate the offset based on page size and number
        $offset = ($pageNumber - 1) * $pageSize;

        try {
            // Build the SQL query with a WHERE clause to filter by name if provided
            $sql    = 'SELECT * FROM items';
            $params = [];

            if ($nameFilter || $idFilter) {
                $sql .= ' WHERE';
                if ($nameFilter) {

                    $sql .= ' name LIKE :name';
                    $params['name'] = '%' . $nameFilter . '%';
                }
                if ($idFilter) {
                    $sql .= ' id = :id';
                    $params['id'] = $idFilter;
                }
            }

            $sql .= ' LIMIT :limit OFFSET :offset';

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

            if ($nameFilter) {
                $stmt->bindParam(':name', $params['name'], PDO::PARAM_STR);
            }
            if ($idFilter) {
                $stmt->bindParam(':id', $params['id'], PDO::PARAM_INT);
            }

            $stmt->execute();

            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Handle PDO-related exceptions
            $response->getBody()->write(json_encode(["message" => "PDO Exception: " . $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Create a response array with different data types
        foreach ($items as &$item) {

            $item['name']         = (string) $item['name'];
            $item['description']  = (string) $item['description'];
            $item['numberValue']  = (float) $item['numberValue'];
            $item['booleanValue'] = (bool) $item['booleanValue'];
            $item['arrayValue']   = json_decode($item['arrayValue'], true);
            $item['objectValue']  = json_decode($item['objectValue']);

        }

        // return $response;
        $responseArray = [
            'pageSize' => $pageSize,
            'pageNumber' => $pageNumber,
            'data' => $items
        ];

        // Encode the response data as JSON
        $responseData = json_encode($responseArray, JSON_UNESCAPED_UNICODE);

        // Write the response data to the body
        $response->getBody()->write($responseData);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function postItem(Request $request, Response $response)
    {

        $pdo  = $this->container->get(PDO::class);
        $body = $request->getBody();
        $data = json_decode($body, true);

        // Validate data
        try {
            v::key('name', v::stringType())->assert($data);
            v::key('description', v::stringType())->assert($data);
            v::key('numberValue', v::number())->assert($data);
            v::key('booleanValue', v::boolType())->assert($data);
            v::key('arrayValue', v::arrayType())->assert($data);
            v::key('objectValue',
                v::key('key',
                    v::key('key2', v::intType()))
            )->assert($data);
        } catch (NestedValidationException $exception) {
            $response->getBody()->write(json_encode(['messagee' => $exception->getMessages()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO items (name, description,numberValue,booleanValue,arrayValue,objectValue) VALUES (?,
    ?,?,?,?,?)');
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['numberValue'],
                $data['booleanValue'],
                json_encode($data['arrayValue']),
                json_encode($data['objectValue'])
            ]);

            $insertedItemId = $pdo->lastInsertId();
            // Fetch the entire row based on the auto-incremented ID
            $stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id");
            $stmt->bindParam(':id', $insertedItemId, PDO::PARAM_INT);
            $stmt->execute();

            $lastInsertedData = $stmt->fetch(PDO::FETCH_ASSOC);


            $response->getBody()->write(json_encode(['message' => 'Item created', 'id' => $insertedItemId, 'data' => $lastInsertedData]));
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(["message" => "PDO Exception: " . $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

    }

    public function putItem(Request $request, Response $response, $args)
    {

        $pdo  = $this->container->get(PDO::class);
        $id   = $args['id'];
        $body = $request->getBody();
        $data = json_decode($body, true);

        // Check if the ID exists before updating
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM items WHERE id = ?');
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            if ($count == 0) {
                $response->getBody()->write(json_encode(['message' => 'Item not found', 'id' => $id]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(["message" => "PDO Exception: " . $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validate data
        try {
            v::key('name', v::stringType())->assert($data);
            v::key('description', v::stringType())->assert($data);
            v::key('numberValue', v::number())->assert($data);
            v::key('booleanValue', v::boolType())->assert($data);
            v::key('arrayValue', v::arrayType())->assert($data);
            v::key('objectValue',
                v::key('key',
                    v::key('key2', v::intType()))
            )->assert($data);
        } catch (NestedValidationException $exception) {
            $response->getBody()->write(json_encode(['messagee' => $exception->getMessages()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $stmt = $pdo->prepare('UPDATE items SET name = ?, description = ?, numberValue = ?, booleanValue= ?, arrayValue= ?, objectValue=
    ? WHERE id = ?');
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['numberValue'],
                $data['booleanValue'],
                json_encode($data['arrayValue']),
                json_encode($data['objectValue']),
                $id
            ]);
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(["message" => "PDO Exception: " . $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['message' => 'Item updated', 'id' => $id, 'data' => $data]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteItem(Request $request, Response $response, $args)
    {

        $pdo = $this->container->get(PDO::class);
        $id  = $args['id'];

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM items WHERE id = ?');
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            if ($count == 0) {
                $response->getBody()->write(json_encode(['message' => 'Item not found', 'id' => $id]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(["message" => "PDO Exception: " . $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM items WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(["message" => "PDO Exception: " . $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['message' => 'Item deleted', 'id' => $id]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
