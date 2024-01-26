<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Firebase\JWT\JWT;
use Slim\Psr7\Response;


function getJwtTokenFromRequest(Request $request)
{
    $authorizationHeader = $request->getHeaderLine('Authorization');
    $token               = preg_replace('/^Bearer\s/', '', $authorizationHeader);
    return $token;
}
function checkIfItemExists($pdo, $id, $response)
{
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM items WHERE id = ?');
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        if ($count == 0) {
            $response->getBody()->write(json_encode(['message' => 'Item not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    } catch (PDOException $e) {
        echo "PDO Exception: " . $e->getMessage();
    }
}

function validateRequest($data)
{
    $expected_data = [
        'name' => 'string',
        'description' => 'string',
        'floatData' => 'integer',
        'boolData' => 'boolean',
        'arrayData' => 'array',
        'objectData' => 'object',
    ];

    $validation_errors = [];

    // Check if all expected keys are present in the POST data
    $missing_keys = array_diff(array_keys($expected_data), array_keys($data));

    if (!empty($missing_keys)) {
        $validation_errors[] = 'Missing keys: ' . implode(', ', $missing_keys);
    }

    // Validate data types
    foreach ($expected_data as $key => $type) {
        // object or associative array
        if ($type == 'object' && is_array($data[$key]) && array_values($data[$key]) !== $data[$key]) {
            continue;
        } else {
            if (gettype($data[$key]) !== $type) {
                $validation_errors[] = "{$key} must be of type {$type}";
                // $validation_errors[] = gettype($data[$key]);
            }
        }
    }

    // If there are validation errors, return the error message
    if (!empty($validation_errors)) {
        return implode(', ', $validation_errors);
    }

    // If validation passes, return null or any other value you prefer
    return null;
}
function sanitizeAndValidateData($data, $response)
{
    $name        = isset($data['name']) ? filter_var($data['name'], FILTER_SANITIZE_STRING) : null;
    $description = isset($data['description']) ? filter_var($data['description'], FILTER_SANITIZE_STRING) : null;
    $floatData   = isset($data['floatData']) ? filter_var($data['floatData'], FILTER_VALIDATE_FLOAT) : null;
    $boolData    = isset($data['boolData']) ? filter_var($data['boolData'], FILTER_VALIDATE_BOOLEAN) : null;
    $arrayData   = $data['arrayData'];
    $objectData  = (object) ($data['objectData']);

    if (!is_string($name)) {
        $response->getBody()->write(json_encode(['message' => 'Invalid name data']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    if (!is_string($description)) {
        $response->getBody()->write(json_encode(['message' => 'Invalid description data']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    if (!is_float($floatData)) {
        $response->getBody()->write(json_encode(['message' => 'Invalid float data']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    if (!is_bool($boolData)) {
        $response->getBody()->write(json_encode(['message' => 'Invalid bool data']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    if (!is_array($arrayData)) {
        $response->getBody()->write(json_encode(['message' => 'Invalid array data']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    if (!is_object($objectData)) {
        $response->getBody()->write(json_encode(['message' => 'Invalid object data']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // You can further process the sanitized and validated data as needed.

    return ['name' => $name, 'description' => $description, 'floatData' => $floatData, 'boolData' => $boolData, 'arrayData' => $arrayData, 'objectData' => $objectData];
}
function getDeviceType()
{
    $user_agent      = $_SERVER['HTTP_USER_AGENT'];
    $mobile_keywords = array('Mobile', 'Android', 'iPhone', 'iPad', 'Windows Phone');
    $tablet_keywords = array('iPad', 'Android', 'Tablet', 'Kindle');

    foreach ($mobile_keywords as $keyword) {
        if (stripos($user_agent, $keyword) !== false) {
            return 'Mobile';
        }
    }

    foreach ($tablet_keywords as $keyword) {
        if (stripos($user_agent, $keyword) !== false) {
            return 'Tablet';
        }
    }

    return 'Desktop';
}

function removeDirectory($dir)
{
    if (!is_dir($dir)) {
        return;
    }

    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            if (is_dir("$dir/$file")) {
                removeDirectory("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
    }

    rmdir($dir);
}
function generateConfirmationCode($length = 32)
{
    return bin2hex(random_bytes($length));
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


function responseFunc(Response $response, $responseDataArray, $statusCode = 200)
{
    $responseData = $responseDataArray;
    if (str_split($statusCode)[0] != 2) {

        $responseData['success'] = 'false';
    } else {
        $responseData['success'] = 'true';
    }

    // $responseData['data']['message'] = $message;
    $responseData['data']['statusCode'] = $statusCode;
    $response->getBody()->write(json_encode($responseData));
    $response = $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    return $response;
}