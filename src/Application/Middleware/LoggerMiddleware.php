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

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerInterface::class);
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        $status = $response->getStatusCode();

        // Handle 404 Not Found and log it as an error
        if ($status == 404) {

            $this->logger->error("$status Not Found: " . $request->getMethod() . ' ' . $request->getUri());

        } else if ($status >= 400 && $status <= 600) {

            $this->logger->error("$status " . $request->getMethod() . ' ' . $request->getUri());
        } else {

            $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';

            $this->logger->info($status . ' ' . $request->getMethod() . ' ' . $request->getUri() . ' ' . $remoteAddr);
        }

        return $response;
    }
}