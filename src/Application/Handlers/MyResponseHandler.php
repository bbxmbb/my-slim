<?php

namespace App\Application\Handlers;

use Psr\Http\Message\ResponseInterface as Response;

class MyResponseHandler
{
    public static function handleResponse(Response $response, $responseDataArray, $statusCode = 200)
    {
        $responseData = self::processData($responseDataArray, $statusCode);

        $response->getBody()->write(json_encode($responseData));
        $response = $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);

        return $response;
    }

    private static function processData($responseDataArray, $statusCode)
    {
        $responseData = $responseDataArray;

        if (self::isStatusCodeNot2xx($statusCode)) {
            $responseData['success'] = 'false';
        } else {
            $responseData['success'] = 'true';
        }

        $responseData['data']['statusCode'] = $statusCode;

        return $responseData;
    }

    private static function isStatusCodeNot2xx($statusCode)
    {
        return str_split($statusCode)[0] != 2;
    }
}
