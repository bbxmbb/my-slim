<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => true, // Should be set to false in production
                'logError' => false,
                'logErrorDetails' => false,
                'defaultTimezone' => [
                    'name' => 'Asia/Bangkok',
                    'time' => '+07:00'
                ],
                'logger' => [
                    'name' => 'my-slim',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                    'enable' => true
                ],
                "db" => [
                    'driver' => 'mysql',
                    'host' => $_ENV['DB_HOST'],
                    'username' => $_ENV['DB_USER'],
                    'database' => $_ENV['DB_NAME'],
                    'password' => $_ENV['DB_PASS'],
                    'charset' => 'utf8',
                ],
                "view" => [
                    'template_path' => __DIR__ . '/../src/Application/Views',
                    'twig' => [
                        // 'cache' => __DIR__ . '/../var/cache/twig',
                        'cache' => false,
                        'debug' => true,
                    ],
                ],
                'mailer' => [
                    //for hostinger
                    // 'host' => 'smtp.hostinger.com',
                    // 'port' => 465,
                    // 'encryption' => 'ssl',
                    'host' => 'smtp.gmail.com',
                    'port' => 587,
                    'encryption' => 'false',
                    'username' => $_ENV["MAIN_EMAIL"],
                    'password' => $_ENV["MAIN_PASSWORD"],
                    'from' => [
                        'email' => $_ENV["MAIN_EMAIL"],
                        'name' => $_ENV["MAIN_NAME"]
                    ]
                ],
                'redis' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'connectionTimeout' => 1
                ],
                'rateLimit' => [
                    'enable' => false,
                    'refillPeriod' => 60,
                    'maxCapacity' => 10
                ]
            ]);
        }
    ]);
};
