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
use App\Application\Models\ImageModel;
use Respect\Validation\Validator as v;
use Illuminate\Database\Schema\Blueprint;
use App\Application\Services\ImageService;
use App\Application\Controllers\Controller;
use App\Application\Services\ImageServices;
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
        $status                = $queryParams['status'] ?? 1;

        $pageSize   = $queryParams['pageSize'] ?? 1000; // Default page size
        $pageNumber = $queryParams['pageNumber'] ?? 1; // Default page number

        $items      = $itemModel->getItems($idFilter, $nameFilter, $createdDateFromFilter, $createdDateToFilter, $pageSize, $pageNumber);
        $table_name = 'items';

        $sql = "SELECT $table_name.*,
                    (GROUP_CONCAT(img.filename)) filename,
                    (GROUP_CONCAT(img.original_filename)) original_filename
				FROM `$table_name`
                left join (SELECT filename,table_id,table_name,original_filename FROM image ) img 
                on img.table_id =$table_name.id
                and img.table_name='$table_name'
            
                where DATE(`$table_name`.created_at) BETWEEN '$createdDateFromFilter' AND '$createdDateToFilter'";

        $sql .= "GROUP BY $table_name.id";

        // var_dump($sql);
        // exit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $items         = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        $amount = $data["amount"] ?? 1;
        self::validateData($data);

        for ($i = 0; $i < $amount; $i++) {
            if ($itemModel->postItems($data) === false) {
                $responseData['data']['error'] = $itemModel->getLastException();
                return MyResponseHandler::handleResponse($response, $responseData, 400);
            }
        }


        $insertedItemId = $pdo->lastInsertId();

        $lastInsertedData = $itemModel->getItems($insertedItemId);

        $responseData['data']['message'] = 'Item created';
        $responseData['data']['id']      = $insertedItemId;
        $responseData['data']['data']    = $lastInsertedData;

        return MyResponseHandler::handleResponse($response, $responseData, 201);

    }
    public function postItemWithImage(Request $request, Response $response): Response
    {

        $jwt_token = $request->getAttribute('jwt_token');
        $pdo       = $this->container->get(PDO::class);

        $itemModel    = new ItemModel($pdo);
        $imageModel   = new ImageModel($pdo);
        $imageService = new ImageService();

        $data = $request->getParsedBody();

        $uploadedFiles = $request->getUploadedFiles();

        $tableName = "items";

        try {
            $pdo->beginTransaction();
            for ($i = 0; $i < $data["amount"]; $i++) {

                if ($itemModel->postItems($data) === false) {

                    $responseData['data']['error'] = $itemModel->getLastException();
                    return MyResponseHandler::handleResponse($response, $responseData, 400);
                } else {

                    $insertedItemId = $pdo->lastInsertId();

                    if (!empty($uploadedFiles)) {

                        $uploadPath = __DIR__ . '/../../../public/uploads/';

                        foreach ($uploadedFiles['image'] as $uploadedFile) {
                            $result = $imageService->checkAndProcessImage($uploadedFile, $uploadPath);
                            if ($result === false) {
                                continue;
                            }
                            $imageData = [
                                "created_by" => $jwt_token->username,
                                "original_filename" => $result['original_path'],
                                "filename" => $result['thumbnail_path'],
                                "table_name" => $tableName,
                                "table_id" => $insertedItemId
                            ];

                            if ($imageModel->insert($imageData)->execute() === false) {

                                $responseData['data']['error'] = $imageModel->getLastException();
                                return MyResponseHandler::handleResponse($response, $responseData, 400);
                            }
                        }

                        $responseData['data']['message'] = 'Image Upload ';
                    } else {
                        $responseData['data']['message'] = 'No Image Upload';
                    }
                }
            }
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $responseData['data']['error'] = $e->getMessage();
            return MyResponseHandler::handleResponse($response, $responseData, 400);
        }


        $responseData['data']['message'] .= 'Item created ';
        $responseData['data']['id']             = $insertedItemId;
        $responseData['data']['original_path']  = $dataExport['original_filename'] ?? null;
        $responseData['data']['thumbnail_path'] = $dataExport['filename'] ?? null;

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

}
