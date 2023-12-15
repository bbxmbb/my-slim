<?php

namespace App\Application\Middleware;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;

class LoggerMiddleware implements MiddlewareInterface
{
    private $logger;

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->logger = $container->get(LoggerInterface::class);
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        // Log information about the request
        $this->logger->info($request->getMethod() . ' ' . $request->getUri() . ' ' . $remoteAddr);

        // Continue to the next middleware or route handler
        $response = $handler->handle($request);

        return $response;
    }
}