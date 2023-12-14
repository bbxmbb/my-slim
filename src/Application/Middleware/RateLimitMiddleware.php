<?php

namespace App\Application\Middleware;

use App\Application\Settings\SettingsInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    private ContainerInterface $container;

    private $rateLimitSettings;
    public function __construct(ContainerInterface $container)
    {

        $this->rateLimitSettings = $container->get(SettingsInterface::class)->get('rateLimit');
    }
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //by pass this middleware when the remote address is undefined 

        if (!isset($_SERVER['REMOTE_ADDR'])) {
            $response = $handler->handle($request);
            return $response;
        }

        //by pass this middleware when disable 

        if ($this->rateLimitSettings['enable'] == 0) {
            $response = $handler->handle($request);
            return $response;
        }

        $ip = $_SERVER['REMOTE_ADDR'];

        if (!isset($_SESSION['rate_limit'][$ip])) {

            $_SESSION['rate_limit'][$ip] = ['timestamp' => time(), 'count' => 1];

        } else {

            $currentTime = time();

            $elapsedTime = $currentTime - $_SESSION['rate_limit'][$ip]['timestamp'];

            if ($elapsedTime > $this->rateLimitSettings['refillPeriod']) {

                $_SESSION['rate_limit'][$ip] = ['timestamp' => $currentTime, 'count' => 1];

            } else {

                if ($_SESSION['rate_limit'][$ip]['count'] >= $this->rateLimitSettings['maxCapacity']) {

                    $response = new Response;
                    $response->getBody()->write((string) ('Rate limit exceeded'));

                    return $response
                        ->withStatus(429)
                        ->withHeader('Content-Type', 'application/json')
                        ->withHeader('X-RateLimit-Remaining', '0');
                }

                $_SESSION['rate_limit'][$ip]['count']++;
            }
        }

        $remainingCount = max(0, $this->rateLimitSettings['maxCapacity'] - $_SESSION['rate_limit'][$ip]['count']);
        $response       = $handler->handle($request);

        return $response->withHeader('X-RateLimit-Remaining', (string) $remainingCount);
    }
}
