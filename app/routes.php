<?php

declare(strict_types=1);

use App\Application\Controllers\ItemController;
use App\Application\Controllers\NotFoundController;
use App\Application\Controllers\HomeController;
use App\Application\Middleware\LoggerMiddleware;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Views\Twig;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Actions\User\ListUsersAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->get('/', function (Request $request, Response $response) {
        // session_start();
        $session = $request->getAttribute('session');
        // echo "<pre>";
        // var_dump(($_SESSION['rate_limit']));
        // print_r("hello");
        // echo "</pre>";
        $response->getBody()->write(json_encode('hello'));
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->get('/home', HomeController::class . ':index');

    $app->get('/twig', function (Request $request, Response $response) {
        //using twig class response time decrease about 33%
        $response = $this->get(Twig::class)->render($response, 'index.twig');

        return $response;
    });

    $app->get('/redis', function (Request $request, Response $response) {

        $redis = $this->get(Redis::class);

        $info = $redis->info();

        $response = $response->withHeader('X-Redis-Enable', '1');
        $response = $response->withHeader('X-Redis-Memory-Used', $info['used_memory']);

        $redis->close();
        return $response;
    });

    $app->get('/db', function (Request $request, Response $response) {

        $time = microtime(true);
        $pdo  = $this->get(PDO::class);

        $diff = microtime(true) - $time;
        $stmt = $pdo->prepare('SELECT * FROM items');
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $response->getBody()->write((string) ($diff * 1000));
        // $response->getBody()->write(json_encode($items));
        return $response
            // ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    });

    $app->get('/logger', function (Request $request, Response $response) {

        $logger = $this->get(LoggerInterface::class);
        var_dump($logger);
        $logger->info($request->getUri()->getPath() . ' ' . $_SERVER['REMOTE_ADDR']);


        $response->getBody()->write('logdata');
        return $response
            // ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    });

    $app->group('/items', function (Group $group) {
        $group->get('', ItemController::class . ':getItem');
        $group->post('', ItemController::class . ':postItem');
        $group->put('/{id}', ItemController::class . ':putItem');
        $group->delete('/{id}', ItemController::class . ':deleteItem');
    })->add(LoggerMiddleware::class);


    // $app->options('/{routes:.*}', function (Request $request, Response $response) {
    //     // CORS Pre-Flight OPTIONS Request Handler
    //     return $response;
    // });

    $app->get('/{routes:.*}', NotFoundController::class . ':index')->add(LoggerMiddleware::class);
};
