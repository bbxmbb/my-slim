<?php

namespace App\Application\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Controller
{
    protected ContainerInterface $container;

    protected LoggerInterface $logger;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->logger = $this->container->get(LoggerInterface::class);
    }
    protected function logData($methodName)
    {

        $this->logger->info(get_class($this) . ' ' . $methodName . ' ' . $_SERVER['REMOTE_ADDR']);
    }
}
