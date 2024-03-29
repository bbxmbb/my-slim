<?php
namespace App\Application\Controllers;

use PDO;
use PDOException;
use Google_Client;
use Slim\Views\Twig;
use Slim\Psr7\Response;
use PHPMailer\PHPMailer\PHPMailer;
use App\Application\Models\UserModel;
use Psr\Container\ContainerInterface;
use Respect\Validation\Validator as v;
use App\Application\Models\SettingsModel;
use App\Application\Helpers\JwtTokenGenerator;
use App\Application\Handlers\MyResponseHandler;
use App\Application\Settings\SettingsInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Exceptions\NestedValidationException;

class SettingController extends Controller
{
    public function settingsView(Request $request, Response $response, $args)
    {

        $jwt_token   = $request->getAttribute('jwt_token');
        $queryParams = $request->getQueryParams();

        $pdo           = $this->container->get(PDO::class);
        $settingsModel = new SettingsModel($pdo);
        $userModel     = new UserModel($pdo);
        $user          = $userModel->findAll()->where("email", "=", $jwt_token->username)->execute('fetch');

        if ($user === false) {
            $responseData['data']['message'] = $userModel->getLastException();
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
            return $response;
        }

        $settings = $settingsModel->getLastSettings();
        $data     = [
            'name' => $args['name'] ?? 'Guest',
            'age' => $queryParams['age'] ?? null,
            'email' => $jwt_token->username ?? 'Guest',
            'token_expired' => $jwt_token->exp ?? null,
            'base_url' => $_ENV["MYPATH"],
            'allowRegister' => $settings["register"],
            'allowRegisterWithGoogle' => $settings["register_with_google"],
            'loginWithGoogle' => $settings["login_with_google"],
            'clientId' => $settings["client_id"],
            'clientSecret' => $settings["client_secret"],
            'userRole' => $user["user_role"]
        ];
        $view     = $this->container->get(Twig::class);

        // Cache for 1 hour
        //this only show on the header but you need to manualyy make a cron job for delete the cache page
        $response = $response->withHeader('Cache-Control', 'public, max-age=60');
        return $view->render($response, '/admin/settings.twig', $data);
    }
    public function updateSettings(Request $request, Response $response, array $args): Response
    {

        $jwt_token     = $request->getAttribute('jwt_token');
        $pdo           = $this->container->get(PDO::class);
        $settingsModel = new SettingsModel($pdo);

        $data = $request->getParsedBody();

        try {
            v::key('register', v::boolType())
                ->key('registerWithGoogle', v::boolType())->assert($data);
        } catch (NestedValidationException $e) {
            throw new \Exception(current($e->getMessages()));
        }
        $settings = $settingsModel->getLastSettings();

        $updateData = [
            'register' => $data['register'],
            'register_with_google' => $data['registerWithGoogle'],
            'login_with_google' => $data["loginWithGoogle"],
            'client_id' => $data["clientId"],
            'client_secret' => $data["clientSecret"],
        ];

        foreach ($settings as $key => $value) {
            if (isset($updateData[$key]) && $updateData[$key] != $value) {
                $dataToInsert = [
                    'key_name' => $key,
                    'value' => $updateData[$key],
                    'user' => $jwt_token->username
                ];
                if ($settingsModel->insert($dataToInsert)->execute() === false) {
                    $responseData['data']['message'] = $settingsModel->getLastException();
                    $response                        = responseFunc($response, $responseData, 500);
                    return $response;
                }
            }
        }

        $responseData['data']['message'] = 'Settings Updated';
        $responseData['data']['data']    = $data;

        return MyResponseHandler::handleResponse($response, $responseData, 200);

    }
}