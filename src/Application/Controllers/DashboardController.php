<?php
namespace App\Application\Controllers;

use PDO;
use PDOException;
use Google_Client;
use Slim\Views\Twig;
use Slim\Psr7\Response;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use Respect\Validation\Validator as v;
use App\Application\Models\SettingsModel;
use App\Application\Helpers\JwtTokenGenerator;
use App\Application\Handlers\MyResponseHandler;
use App\Application\Settings\SettingsInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Exceptions\NestedValidationException;

class DashboardController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {

        $jwt_token   = $request->getAttribute('jwt_token');
        $queryParams = $request->getQueryParams();
        // $view = Twig::fromRequest($request);
        $baseUrl = $request->getUri();
        $data    = [
            'name' => $args['name'] ?? 'Guest',
            'age' => $queryParams['age'] ?? null,
            'email' => $jwt_token->username ?? 'Guest',
            'token_expired' => $jwt_token->exp ?? null,
            'base_url' => $_ENV["MYPATH"]
        ];
        $view    = $this->container->get(Twig::class);

        // Cache for 1 hour
        //this only show on the header but you need to manualyy make a cron job for delete the cache page
        $response = $response->withHeader('Cache-Control', 'public, max-age=60');
        return $view->render($response, '/admin/items/index.twig', $data);
    }

}