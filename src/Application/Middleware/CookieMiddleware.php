<?php
namespace App\Application\Middleware;

use Exception;
use Google_Client;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use Slim\Psr7\Response as Response;
use App\Application\Handlers\MyResponseHandler;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Firebase\JWT\ExpiredException;

class CookieMiddleware implements Middleware
{

    public function process(Request $request, RequestHandler $handler): Response
    {
        $cookieParams = $request->getCookieParams();

        $response = new Response();

        $isBackendRequest = strpos($request->getHeaderLine('User-Agent'), "PostmanRuntime") !== false;

        if ($isBackendRequest) {
            if (
                !isset($cookieParams['jwt_token']) &&
                !isset($_SESSION['jwt_token']) &&
                !isset($cookieParams['g_token'])
            ) {
                $responseData['data']['message'] = 'Token not found';
                $response                        = MyResponseHandler::handleResponse($response, $responseData, 401);
                return $response;
            }
        }

        if (isset($cookieParams['jwt_token']) || isset($_SESSION['jwt_token'])) {

            $token = $cookieParams['jwt_token'] ?? $_SESSION['jwt_token'];
            try {
                $decoded = JWT::decode($token, new Key($_ENV['SECRET_KEY'], 'HS256')); // Replace with your actual JWT secret

            } catch (ExpiredException $e) {

                $this->unsetToken();
                echo "Error: Expired token - " . $e->getMessage();

            } catch (\Exception $e) {
                // Handle other exceptions
                echo "Error: " . $e->getMessage();
            }

            $request = $request->withAttribute('jwt_token', $decoded ?? null);
            return $handler->handle($request);

        } else if (isset($cookieParams['g_token'])) {

            $token  = $cookieParams['g_token'];
            $client = new Google_Client();
            // $client = new Client(['client_id' => $_ENV['CLIENT_ID']]);
            $client->setClientId($_ENV['CLIENT_ID']);
            $client->setClientSecret($_ENV['CLIENT_SECRET']);
            $decoded = $client->verifyIdToken($token); // Replace with the ID token you want to verify

            $request = $request->withAttribute('jwt_token', $decoded ?? null);
            return $handler->handle($request);
        } else {

            $this->unsetToken();

            if ($isBackendRequest) {
                $responseData['data']['message'] = 'Invalid Token Or token has expired';
                $response                        = MyResponseHandler::handleResponse($response, $responseData, 401);
                return $response;
            } else {

                return $response->withStatus(302)->withHeader("Location", $_ENV["MYPATH"] . '/login?message=Token has Expired Please Login Again&status=false');
            }
        }
    }
    private function unsetToken()
    {

        setcookie('jwt_token', '', time() - 1, '/');
        setcookie('g_token', '', time() - 1, '/');
        unset($_COOKIE['g_token']);
        unset($_COOKIE['jwt_token']);
        unset($_SESSION['jwt_token']);
    }
}