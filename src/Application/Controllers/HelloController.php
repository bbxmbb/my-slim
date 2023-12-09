<?php
namespace App\Application\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HelloController {
    public function index(Request $request, Response $response) {
        $response->getBody()->write(__CLASS__);
        return $response;
    }
}