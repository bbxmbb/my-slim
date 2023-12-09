<?php

declare(strict_types=1);

use Slim\App;
use App\Application\Middleware\RedisMiddleware;
use App\Application\Middleware\OpcacheMiddleware;
use App\Application\Middleware\SessionMiddleware;
use App\Application\Middleware\RateLimitMiddleware;

return function (App $app) {
    $app->add(SessionMiddleware::class);
    $app->add(OpcacheMiddleware::class);
    $app->add(RateLimitMiddleware::class);
    // $app->add(RedisMiddleware::class);
};
