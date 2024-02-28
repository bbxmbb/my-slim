<?php
namespace App\Application\Middleware;

use PDO;
use Exception;
use Google_Client;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Slim\Psr7\Response as Response;
use App\Application\Models\SettingsModel;
use App\Application\Handlers\MyResponseHandler;

use Psr\Http\Server\MiddlewareInterface as Middleware;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CookieMiddleware implements Middleware
{

    public function process(Request $request, RequestHandler $handler): Response
    {
        $cookieParams = $request->getCookieParams();

        $response = new Response();

        $isBackendRequest = strpos($request->getHeaderLine('User-Agent'), "PostmanRuntime") !== false;

        if (isset($cookieParams['jwt_token']) || isset($_SESSION['jwt_token'])) {

            $token = $cookieParams['jwt_token'] ?? $_SESSION['jwt_token'];
            try {
                $decoded = JWT::decode($token, new Key($_ENV['SECRET_KEY'], 'HS256')); // Replace with your actual JWT secret
            } catch (Exception $e) {
                return $this->expiredToken($response, $isBackendRequest, $request);
            }

            $request = $request->withAttribute('jwt_token', $decoded ?? null);
            return $handler->handle($request);

        } else {
            return $this->expiredToken($response, $isBackendRequest, $request);
        }
    }

    private function expiredToken($response, $isBackendRequest, $request)
    {
        // Get current URL path
        $path = substr($request->getUri()->getPath(), strlen($_ENV["BASEPATH"]));

        // Get query string
        $query = $request->getUri()->getQuery();

        // Combine path and query string
        $redirect_url = $path;
        if (!empty($query)) {
            $redirect_url .= '?' . $query;
        }

        setcookie('jwt_token', '', time() - 1, '/');
        unset($_COOKIE['jwt_token']);
        unset($_SESSION['jwt_token']);

        if ($isBackendRequest) {

            $responseData['data']['message'] = 'Invalid Token Or token has expired';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 401);
            return $response;
        } else {
            return $response->withStatus(302)->withHeader("Location", $_ENV["MYPATH"] . '/login?message=Token has Expired Please Login Again&status=false&redirect_url=' . $redirect_url);

        }
    }
}