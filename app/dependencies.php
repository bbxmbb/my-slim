<?php

declare(strict_types=1);

use Monolog\Logger;
use Slim\Views\Twig;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use App\Application\Settings\SettingsInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger         = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

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

            return $twig;
        },
        Redis::class => function (ContainerInterface $c) {
            $settings      = $c->get(SettingsInterface::class);
            $redisSettings = $settings->get('redis');
            $redis         = new Redis;
            $redis->connect($redisSettings['host'], $redisSettings['port'], $redisSettings['connectionTimeout']);
            return $redis;
        }
    ]);
};
