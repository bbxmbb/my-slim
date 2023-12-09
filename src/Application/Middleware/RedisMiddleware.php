<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Settings\SettingsInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class RedisMiddleware implements Middleware {
    private $redis;

    private $redisSettings;

    public function __construct(ContainerInterface $container) {
        // global $container;
        $this->redisSettings = $container->get(SettingsInterface::class)->get('redis');

        try {
            // Attempt to create a Redis connection
            $this->redis = new \Redis();
            $this->redis->connect(
                $this->redisSettings['host'],
                $this->redisSettings['port'],
                $this->redisSettings['connectionTimeout']
            );
        } catch (\Exception $e) {
            // Handle connection error (you can log the error if needed)
            // Set $this->redis to null to indicate a failed connection
            $this->redis = null;
        }
    }
    public function process(Request $request, RequestHandler $handler): Response {

        //Set Attribute for future use
        $request = $request->withAttribute('redis', $this->redis);

        // Set header indicating Redis is enabled
        $response = $handler->handle($request)->withHeader('X-Redis-Enabled', '1');
        if($this->redis != null) {

            $info = $this->redis->info();

            // Set header indicating Redis is connected
            $response = $response->withHeader('X-Redis-Memory-Used', $info['used_memory']);

        } else {

            // Set header indicating Redis extension is enabled but not connected
            $response = $response->withHeader('X-Redis-Connection-Status', 'Not connected');

            $response = $response->withHeader('X-Redis-Usage', '0');
        }
        return $response;
    }
}