<?php

namespace App\Application\Controllers;

use Slim\Views\Twig;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HomeController extends Controller
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $view = $this->container->get(Twig::class);

        $response = $view->render($response, "index.twig");

        return $response;
    }
}
