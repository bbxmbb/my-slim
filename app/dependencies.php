<?php

declare(strict_types=1);

use Monolog\Logger;
use Slim\Views\Twig;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use PHPMailer\PHPMailer\PHPMailer;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use App\Application\Settings\SettingsInterface;
use App\Application\Helpers\AssetExtension;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger         = new Logger($loggerSettings['name']);

            if ($loggerSettings['enable']) {

                $processor = new UidProcessor();
                $logger->pushProcessor($processor);

                $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
                $logger->pushHandler($handler);
            } else {
                // Disable logging by removing all handlers
                $logger->setHandlers([]);
            }
            return $logger;
        },
        PDO::class => function (ContainerInterface $c) {

            $settings   = $c->get(SettingsInterface::class);
            $dbSettings = $settings->get('db');

            $host     = $dbSettings['host'];
            $dbname   = $dbSettings['database'];
            $username = $dbSettings['username'];
            $password = $dbSettings['password'];
            $charset  = $dbSettings['charset'];
            $dsn      = "mysql:host=$host;dbname=$dbname;charset=$charset";
            return new PDO($dsn, $username, $password);
        },
        Twig::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $viewSettings = $settings->get('view');

            $twig = Twig::create($viewSettings['template_path'], $viewSettings['twig']);

            $twig->addExtension(new AssetExtension($_ENV["MYPATH"]));

            return $twig;
        },
        Redis::class => function (ContainerInterface $c) {
            $settings      = $c->get(SettingsInterface::class);
            $redisSettings = $settings->get('redis');
            $redis         = new Redis;
            $redis->connect($redisSettings['host'], $redisSettings['port'], $redisSettings['connectionTimeout']);
            return $redis;
        },
        PHPMailer::class => function (ContainerInterface $c) {
            $settings     = $c->get(SettingsInterface::class);
            $mailerConfig = $settings->get('mailer');

            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host       = $mailerConfig['host'];
            $mailer->SMTPAuth   = true;
            $mailer->Username   = $mailerConfig['username'];
            $mailer->Password   = $mailerConfig['password'];
            $mailer->SMTPSecure = $mailerConfig['encryption'];
            $mailer->Port       = $mailerConfig['port'];

            return $mailer;
        },
        Eloquent::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $capsule = new \Illuminate\Database\Capsule\Manager;
            $capsule->addConnection($settings->get('db'));

            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            return $capsule;
        }
    ]);
};
