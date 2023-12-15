<?php

namespace App\Application\Controllers;

use Psr\Log\LoggerInterface;
use Slim\Routing\RouteContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Controller
{
    protected ContainerInterface $container;

    protected LoggerInterface $logger;

    protected Request $request;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->logger = $this->container->get(LoggerInterface::class);
    }
    protected function logData($route, $methodName)
    {
        $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        $this->logger->info(get_class($this) . ' ' . $route . ' ' . $remoteAddr);
    }
}
