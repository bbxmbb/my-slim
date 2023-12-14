<?php

namespace App\Application\Controllers;

use Slim\Views\Twig;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use App\Application\Settings\SettingsInterface;

class NotFoundController extends Controller
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $this->logData(__FUNCTION__);

        $view = $this->container->get(Twig::class);

        $response = $view->render($response, '404.twig');

        return $response;

    }
}
