<?php

declare(strict_types=1);

use Slim\App;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use PHPMailer\PHPMailer\PHPMailer;
use App\Application\Controllers\AuthController;
use App\Application\Controllers\HomeController;
use App\Application\Controllers\ItemController;
use App\Application\Handlers\MyResponseHandler;
use App\Application\Settings\SettingsInterface;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Middleware\CookieMiddleware;
use App\Application\Middleware\LoggerMiddleware;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Controllers\SettingController;
use App\Application\Controllers\NotFoundController;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Controllers\DashboardController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->get('/', function (Request $request, Response $response) {
        // session_start();
        $session = $request->getAttribute('session');
        $response->getBody()->write("Hello From Twig and Slim. If you see this code mean that it is work!");
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
        $group->get('/all', ItemController::class . ':getAll');
        $group->post('', ItemController::class . ':postItem');
        $group->post('/create', ItemController::class . ':insertItem');
        $group->put('/{id}', ItemController::class . ':putItem');
        $group->put('/update/{id}', ItemController::class . ':changeItem');
        $group->delete('/{id}', ItemController::class . ':deleteItem');
        $group->delete('/delete/{id}', ItemController::class . ':eraseItem');
        $group->get('/test', ItemController::class . ':testMyMethod');
    })->add(CookieMiddleware::class);

    $app->group('', function (Group $group) {

        $group->get('/login', AuthController::class . ':loginView');
        $group->get('/register', AuthController::class . ':registerView');
        $group->get('/resetPassword', AuthController::class . ':resetPasswordView');
        $group->get('/resetPasswordConfirm/{confirmationCode}', AuthController::class . ':resetPasswordConfirmView');
        $group->get('/email-confirm/{confirmationCode}', AuthController::class . ':confirmEmailView');
        $group->get('/tokenValidation', AuthController::class . ':tokenValidation');

        $group->post('/login', AuthController::class . ':userLogin');
        $group->map(['GET', 'POST'], '/logout', AuthController::class . ':userLogout');
        $group->post('/register', AuthController::class . ':userRegister');
        $group->map(['GET', 'POST'], '/loginWithGoogle', AuthController::class . ':userLoginWithGoogle');
        $group->post('/reset-password', AuthController::class . ':resetPassword');
        $group->post('/resetPasswordConfirm', AuthController::class . ':resetPasswordConfirm');

    });

    $app->group('/admin', function (Group $group) {

        $group->get('/settings', SettingController::class . ':settingsView');
        $group->get('[/index]', ItemController::class . ':reportView');
        $group->group('/items', function (Group $group2) {

            $group2->get('/create', ItemController::class . ':createView');
            $group2->get('/update', ItemController::class . ':updateView');
            $group2->get('/report', ItemController::class . ':reportView');

        });


    })->add(CookieMiddleware::class);

    $app->post('/updateSettings', SettingController::class . ':updateSettings')
        ->add(CookieMiddleware::class);
    ;
    $app->get('/testsendmail', function ($request, $response, $args) {

        $mailerConfig = $this->get(SettingsInterface::class)->get('mailer');

        $mail = $this->get(PHPMailer::class);

        $confirmationCode = bin2hex(random_bytes(32));
        $confirmationLink = $_ENV["MYPATH"] . '/confirm/' . $confirmationCode;

        $from_email = $mailerConfig['from']['email'];
        $from_name  = $mailerConfig['from']['name'];
        $to         = "bbombb2535@gmail.com";
        $subject    = 'Email Confirmation';
        $body       = "Click the following link to confirm your email: <a href='$confirmationLink'>$confirmationLink</a>";


        try {
            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            // return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            // return false;
        }
        // $to_email   = "bbombb2535@gmail.com";
        // $subject    = "Simple Email Testing via PHP";
        // $body       = "Hello,nn It is a testing email sent by PHP Script";
        // $from_email = "bbombb.bbombb@gmail.com";
        // $headers    = 'From: ' . $from_email . "\r\n" .
        //     'Reply-To: ' . $from_email . "\r\n" .
        //     'X-Mailer: PHP/' . phpversion();
        // if (mail($to_email, $subject, $body, $headers)) {
        //     echo "Email successfully sent to $to_email...";
        // } else {
        //     echo "Email sending failed...";
        // }
        exit();
        return $response;
    });

    // $app->options('/{routes:.*}', function (Request $request, Response $response) {
    //     // CORS Pre-Flight OPTIONS Request Handler
    //     return $response;
    // });

    $app->get('/{routes:.*}', NotFoundController::class . ':index');
};
