<?php

namespace App\Application\Helpers;

use Firebase\JWT\JWT;

class JwtTokenGenerator
{
    public static function generateJwtToken($userId, $username)
    {
        $issuedAt = time();
        $expire   = $issuedAt + $_ENV["EXPIRE_TIME_IN_SECS"]; // Token will expire in 1 hour (3600 seconds)

        $payload = array(
            'iss' => $_ENV['MYPATH'],
            'aud' => $_ENV['MYPATH'],
            'iat' => $issuedAt,
            'exp' => $expire,
            'expIn' => $_ENV["EXPIRE_TIME_IN_SECS"],
            'userId' => $userId,
            'username' => $username
        );

        // Assuming JWT class is available and you have included the necessary dependencies
        $jwt = JWT::encode($payload, $_ENV['SECRET_KEY'], 'HS256');

        return $jwt;
    }
}
