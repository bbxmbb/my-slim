<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class OpcacheMiddleware implements Middleware {
    public function process(Request $request, RequestHandler $handler): Response {
        $opcacheStatus = opcache_get_status();
        if(is_array($opcacheStatus)) {
            // Set header indicating OPcache is enabled
            $response = $handler->handle($request)->withHeader('X-Opcache-Enabled', '1');
            $response = $response->withHeader('X-Opcache-Cache-Used', $opcacheStatus['memory_usage']['used_memory']);
        } else {
            // Set header indicating OPcache is not enabled
            $response = $handler->handle($request)->withHeader('X-Opcache-Enabled', '0');
        }
        return $response;
    }
}