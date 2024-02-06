<?php
namespace App\Application\Controllers;

use PDO;
use Eloquent;
use PDOException;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;
use App\Application\Models\Item;
use App\Application\Models\ItemModel;
use App\Application\Models\UserModel;
use Respect\Validation\Validator as v;
use Illuminate\Database\Schema\Blueprint;
use App\Application\Controllers\Controller;
use App\Application\Handlers\MyResponseHandler;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Respect\Validation\Exceptions\ValidationException;
use \Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Exception\CustomValidationException;
use Respect\Validation\Exceptions\NestedValidationException;

class ItemController extends Controller
{
    private $view;
    public function createView(Request $request, Response $response, $args)
    {

        $jwt_token   = $request->getAttribute('jwt_token');
        $queryParams = $request->getQueryParams();

        $pdo       = $this->container->get(PDO::class);
        $userModel = new UserModel($pdo);
        $user      = $userModel->findAll()->where("email", "=", $jwt_token->username)->execute('fetch');

        if ($user === false) {
            $responseData['data']['message'] = $userModel->getLastException();
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
            return $response;
        }

        $data = [
            'name' => $args['name'] ?? 'Guest',
            'age' => $queryParams['age'] ?? null,
            'email' => $jwt_token->username ?? 'Guest',
            'token_expired' => $jwt_token->exp,
            'base_url' => $_ENV["MYPATH"],
            'userRole' => $user["user_role"]
        ];

        $view = $this->container->get(Twig::class);

        // Cache for 1 hour
        //this only show on the header but you need to manualyy make a cron job for delete the cache page
        $response = $response->withHeader('Cache-Control', 'public, max-age=60');
        return $view->render($response, '/admin/items/create.twig', $data);
    }
    public function updateView(Request $request, Response $response, $args)
    {

        $jwt_token   = $request->getAttribute('jwt_token');
        $queryParams = $request->getQueryParams();

        $pdo       = $this->container->get(PDO::class);
        $userModel = new UserModel($pdo);
        $user      = $userModel->findAll()->where("email", "=", $jwt_token->username)->execute('fetch');

        if ($user === false) {
            $responseData['data']['message'] = $userModel->getLastException();
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
            return $response;
        }

        $data = [
            'name' => $args['name'] ?? 'Guest',
            'age' => $queryParams['age'] ?? null,
            'email' => $jwt_token->username ?? 'Guest',
            'token_expired' => $jwt_token->exp,
            'base_url' => $_ENV["MYPATH"],
            'userRole' => $user["user_role"]
        ];
        $view = $this->container->get(Twig::class);

        // Cache for 1 hour
        //this only show on the header but you need to manualyy make a cron job for delete the cache page
        $response = $response->withHeader('Cache-Control', 'public, max-age=60');
        return $view->render($response, '/admin/items/update.twig', $data);
    }
    public function reportView(Request $request, Response $response, $args)
    {

        $jwt_token   = $request->getAttribute('jwt_token');
        $queryParams = $request->getQueryParams();

        $pdo       = $this->container->get(PDO::class);
        $userModel = new UserModel($pdo);
        $user      = $userModel->findAll()->where("email", "=", $jwt_token->username)->execute('fetch');

        if ($user === false) {
            $responseData['data']['message'] = $userModel->getLastException();
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
            return $response;
        }

        $data = [
            'name' => $args['name'] ?? 'Guest',
            'age' => $queryParams['age'] ?? null,
            'email' => $jwt_token->username ?? 'Guest',
            'token_expired' => $jwt_token->exp,
            'base_url' => $_ENV["MYPATH"],
            'userRole' => $user["user_role"]
        ];
        $view = $this->container->get(Twig::class);

        // Cache for 1 hour
        //this only show on the header but you need to manualyy make a cron job for delete the cache page
        $response = $response->withHeader('Cache-Control', 'public, max-age=60');
        return $view->render($response, '/admin/items/report.twig', $data);
    }
    public function getItem(Request $request, Response $response): Response
    {

        $pdo       = $this->container->get(PDO::class);
        $itemModel = new ItemModel($pdo);

        $queryParams = $request->getQueryParams();

        $nameFilter            = $queryParams['name'] ?? null;
        $idFilter              = $queryParams['id'] ?? null;
        $createdDateFromFilter = $queryParams['dateFrom'] ?? (date('Y') - 100) . date('-m-d');
        $createdDateToFilter   = $queryParams['dateTo'] ?? date('Y-m-d');

        $pageSize   = $queryParams['pageSize'] ?? 1000; // Default page size
        $pageNumber = $queryParams['pageNumber'] ?? 1; // Default page number

        $items = $itemModel->getItems($idFilter, $nameFilter, $createdDateFromFilter, $createdDateToFilter, $pageSize, $pageNumber);

        $responseArray = [
            'pageSize' => $pageSize,
            'pageNumber' => $pageNumber,
            'data' => $items
        ];
        return MyResponseHandler::handleResponse($response, $responseArray);
    }

    public function postItem(Request $request, Response $response): Response
    {

        $pdo       = $this->container->get(PDO::class);
        $itemModel = new ItemModel($pdo);

        $data = $request->getParsedBody();

        self::validateData($data);

        $itemModel->postItems($data);

        $insertedItemId = $pdo->lastInsertId();

        $lastInsertedData = $itemModel->getItems($insertedItemId);

        $responseData['data']['message'] = 'Item created';
        $responseData['data']['id']      = $insertedItemId;
        $responseData['data']['data']    = $lastInsertedData;

        return MyResponseHandler::handleResponse($response, $responseData, 201);

    }

    public function putItem(Request $request, Response $response, array $args): Response
    {

        $pdo       = $this->container->get(PDO::class);
        $itemModel = new ItemModel($pdo);

        $id   = $args['id'];
        $data = $request->getParsedBody();

        self::validateData($data);

        // Check if the ID exists before updating
        $items = $itemModel->getItems($id);

        if (empty($items)) {
            $responseData['data']['message'] = 'Item not found id:' . $id;

            return MyResponseHandler::handleResponse($response, $responseData, 404);
        }

        $itemModel->putItems($data, $id);

        $responseData['data']['message'] = 'Item updated';
        $responseData['data']['id']      = $id;
        $responseData['data']['data']    = $data;

        return MyResponseHandler::handleResponse($response, $responseData, 200);
    }

    public function deleteItem(Request $request, Response $response, array $args): Response
    {

        $pdo       = $this->container->get(PDO::class);
        $itemModel = new ItemModel($pdo);
        $id        = $args['id'];

        $items = $itemModel->getItems($id);

        if (empty($items)) {
            $responseData['data']['message'] = 'Cannot Delete because item not found id:' . $id;

            return MyResponseHandler::handleResponse($response, $responseData, 404);
        }

        $itemModel->deleteItem($id);

        $responseData['data']['message'] = 'Item deleted';
        $responseData['data']['id']      = $id;

        return MyResponseHandler::handleResponse($response, $responseData, 200);

    }

    private static function validateData(array $data)
    {
        try {
            v::key('name', v::stringType()->notEmpty())
                ->key('description', v::stringType()->notEmpty())
                ->key('numberValue', v::number())
                // ->key('booleanValue', v::boolType())
                // ->key('arrayValue', v::arrayType())
                // ->key(
                //     'objectValue',
                //     v::key(
                //         'key',
                //         v::key('key2', v::intType())
                //     )
                // )
                ->assert($data);

        } catch (NestedValidationException $e) {
            throw new \Exception(current($e->getMessages()));
        }
    }

    public function getAll(Request $request, Response $response)
    {
        $capsule = $this->container->get(Eloquent::class);
        $items   = Item::all();

        $responseData['data']['data'] = $items;
        return MyResponseHandler::handleResponse($response, $responseData, 200);

    }
    public function insertItem(Request $request, Response $response)
    {
        $capsule = $this->container->get(Eloquent::class);
        $data    = $request->getParsedBody();

        if (!Capsule::schema()->hasTable('items')) {
            // If the table does not exist, create it
            Capsule::schema()->create('items', function (Blueprint $table) {
                $table->id(); // Add primary key column
                $table->string('name');
                $table->text('description');
                $table->integer('numberValue');
                $table->boolean('booleanValue');
                $table->json('arrayValue');
                $table->json('objectValue');
                $table->timestamps(); // Add created_at and updated_at columns
            });
        }
        self::validateData($data);

        $items = Item::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'numberValue' => $data['numberValue'],
            'booleanValue' => $data['booleanValue'],
            'arrayValue' => json_encode($data['arrayValue']),
            'objectValue' => json_encode($data['objectValue']),
        ]);

        $responseData['data']['data'] = $items;
        $response                     = MyResponseHandler::handleResponse($response, $responseData, 200);

        return $response;
    }
    public function changeItem(Request $request, Response $response, $args)
    {
        $capsule = $this->container->get(Eloquent::class);
        $itemId  = $args['id'];
        $data    = $request->getParsedBody();

        $item = Item::find($itemId);

        if (!$item) {
            $responseData['data']['data'] = 'Data not Found';
            $response                     = MyResponseHandler::handleResponse($response, $responseData, 200);
            return $response;
        }

        $item->update($data);

        $responseData['data']['data']    = $item;
        $responseData['data']['message'] = "Update succesfully";
        $response                        = MyResponseHandler::handleResponse($response, $responseData, 200);

        return $response;
    }
    public function eraseItem(Request $request, Response $response, $args): Response
    {
        $capsule = $this->container->get(Eloquent::class);
        $itemId  = $args['id'];
        $item    = Item::find($itemId);

        if (!$item) {
            $responseData['data']['data'] = 'Data not Found';
            $response                     = MyResponseHandler::handleResponse($response, $responseData, 200);
            return $response;
        }

        // Delete the item
        $item->delete();

        $responseData['data']['message'] = "Item delete Successfully";
        $response                        = MyResponseHandler::handleResponse($response, $responseData, 200);

        return $response;
    }
    public function testMyMethod(Request $request, Response $response, $args): Response
    {
        $pdo       = $this->container->get(PDO::class);
        $itemModel = new ItemModel($pdo);

        $queryParams = $request->getQueryParams();

        $nameFilter = $queryParams['name'] ?? null;
        $idFilter   = $queryParams['id'] ?? null;
        $pageSize   = $queryParams['pageSize'] ?? 1000; // Default page size
        $pageNumber = $queryParams['pageNumber'] ?? 1; // Default page number

        $sql  = $itemModel->findAll();
        $item = $sql->where("numberValue", "=", "84")->execute();

        // $item = $itemModel->find(100)->execute();
        // var_dump($item);
        // exit;
        $responseData['data']['message'] = $item;
        return MyResponseHandler::handleResponse($response, $responseData, 200);


    }
}
