<?php
namespace App\Application\Controllers;

use PDO;
use PDOException;
use Google_Client;
use Slim\Views\Twig;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use Slim\Psr7\Response;
use PHPMailer\PHPMailer\PHPMailer;
use App\Application\Models\ItemModel;
use App\Application\Models\UserModel;
use Psr\Container\ContainerInterface;
use Respect\Validation\Validator as v;
use App\Application\Helpers\EmailSender;
use App\Application\Models\SettingsModel;
use App\Application\Helpers\JwtTokenGenerator;
use App\Application\Handlers\MyResponseHandler;
use App\Application\Settings\SettingsInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Exceptions\NestedValidationException;

class AuthController extends Controller
{
    private $view;
    public function loginView(Request $request, Response $response, $args)
    {
        $settingsInterface = $this->container->get(SettingsInterface::class);
        $timezone          = $settingsInterface->get('defaultTimezone')['time'];

        $pdo           = $this->container->get(PDO::class);
        $settingsModel = new SettingsModel($pdo);
        $userModel     = new UserModel($pdo);
        $itemModel     = new ItemModel($pdo);

        // Check if the users,item and settings table exists
        if (
            $itemModel->createTableIfNotExist() === false ||
            $userModel->createTableIfNotExist() === false ||
            $settingsModel->createTableIfNotExist($timezone) === false
        ) {
            $responseData["data"]["message"] = $settingsModel->getLastException();
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
            return $response;
        }
        $settings = $settingsModel->getLastSettings();

        if ($settings === false) {
            $responseData["data"]["message"] = $settingsModel->getLastException();
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
            return $response;
        }

        $cookieParams = $request->getCookieParams();

        if (isset($cookieParams['jwt_token']) || isset($_SESSION['jwt_token'])) {
            return $response->withHeader('Location', $_ENV['MYPATH'] . '/admin/items/report')->withStatus(302);
        }
        $queryParams = $request->getQueryParams();

        $baseUrl = $request->getUri();
        $data    = [
            'name' => $args['name'] ?? 'Guest',
            'age' => $queryParams['age'] ?? null,
            'client_id' => $settings['client_id'],
            'base_url' => $_ENV["MYPATH"],
            'registerWithGoogle' => $settings["register_with_google"],
            'register' => $settings["register"],
            'loginWithGoogle' => $settings["login_with_google"]
        ];
        $view    = $this->container->get(Twig::class);

        // $cacheFile = $_SERVER["DOCUMENT_ROOT"] . '/caches/twig/login.twig.cache';

        // $view->getEnvironment()->getCache($cacheFile);
        // $response = $response->withHeader('Cache-Control', 'no-store'); // Do not cache

        //using this to remove cache folder for specific page
        // if (is_dir($cacheFile)) {
        //     removeDirectory($cacheFile);
        //     echo 'Cache folder and its contents removed successfully.';
        // } else {
        //     echo 'Cache folder does not exist.';
        // }
        // $view->getEnvironment()->setCache($cacheFile);

        // Cache for 1 hour
        //this only show on the header but you need to manualyy make a cron job for delete the cache page
        $response = $response->withHeader('Cache-Control', 'public, max-age=60');
        return $view->render($response, '/Auth/login.twig', $data);
    }
    public function registerView(Request $request, Response $response, $args)
    {
        $pdo           = $this->container->get(PDO::class);
        $settingsModel = new SettingsModel($pdo);
        $settings      = $settingsModel->getLastSettings();

        if ($settings["register"] == false) {
            return $response->withHeader('Location', $_ENV['MYPATH'] . '/login?message=Register unavailable&status=false')->withStatus(302);
        }

        $cookieParams = $request->getCookieParams();

        if (isset($cookieParams['jwt_token']) || isset($_SESSION['jwt_token'])) {

            return $response->withHeader('Location', $_ENV['MYPATH'] . '/admin/items/report')->withStatus(302);
        }

        $queryParams = $request->getQueryParams();

        $data = [
            'name' => $args['name'] ?? 'Guest',
            'age' => $queryParams['age'] ?? null,
            'client_id' => $settings['client_id'],
            'base_url' => $_ENV["MYPATH"],
            'registerWithGoogle' => $settings["register_with_google"],
            'register' => $settings["register"]
        ];

        $view = $this->container->get(Twig::class);

        return $view->render($response, '/Auth/register.twig', $data);
    }
    public function confirmEmailView(Request $request, Response $response, $args)
    {
        $pdo       = $this->container->get(PDO::class);
        $userModel = new UserModel($pdo);


        $isBackendRequest = strpos($request->getHeaderLine('User-Agent'), "PostmanRuntime") !== false;


        $confirmationCode = $args['confirmationCode'];

        $resetConfirmationCode = bin2hex(random_bytes(5));

        // Process confirmation code and mark email as confirmed
        $user = $userModel->findAll()->where("confirmation_code", "=", $confirmationCode)->execute()[0] ?? null;

        if (!$user) {

            $responseText = 'Invalid Confirmation Code, User not found';
            if ($isBackendRequest) {
                $responseData['data']['message'] = $responseText;
                $response                        = MyResponseHandler::handleResponse($response, $responseData, 400);
                return $response;
            }

            $status = 'false';
            $url    = $_ENV['MYPATH'] . '/login?message=' . $responseText . '&status=' . $status;

            return $response->withHeader('Location', $url)->withStatus(302);

        }

        if ($user['confirmed']) {

            $responseText = 'This Email is already confirmed';
            if ($isBackendRequest) {
                $responseData['data']['message'] = $responseText;
                $response                        = MyResponseHandler::handleResponse($response, $responseData, 200);
                return $response;
            }

            $status = 'false';
            $url    = $_ENV['MYPATH'] . '/login?message=' . $responseText . '&status=' . $status;

            return $response->withHeader('Location', $url)->withStatus(302);
        }

        $updateData = ['confirmed' => true, 'confirmation_code' => $resetConfirmationCode];
        $userModel->update($updateData)->where('email', '=', $user['email'])->execute();


        $responseText = 'Email has been confirmed';
        if ($isBackendRequest) {

            $responseData['data']['message'] = $responseText;
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 200);
            return $response;
        }

        $status = 'true';
        $url    = $_ENV['MYPATH'] . '/login?message=' . $responseText . '&status=' . $status;

        return $response->withHeader('Location', $url)->withStatus(302);

    }
    public function resetPasswordView(Request $request, Response $response, $args)
    {
        $queryParams = $request->getQueryParams();

        $data = [
            'name' => $args['name'] ?? 'Guest',
            'age' => $queryParams['age'] ?? null,
            'base_url' => $_ENV['MYPATH']
        ];

        $view = $this->container->get(Twig::class);

        return $view->render($response, '/Auth/resetPassword.twig', $data);
    }
    public function resetPasswordConfirmView(Request $request, Response $response, $args)
    {

        $confirmationCode = $args['confirmationCode'];
        $queryParams      = $request->getQueryParams();

        $data = [
            'name' => $args['name'] ?? 'Guest',
            'age' => $queryParams['age'] ?? null,
            'base_url' => $_ENV['MYPATH']
        ];

        $view = $this->container->get(Twig::class);

        return $view->render($response, '/Auth/resetPasswordConfirm.twig', $data);
    }
    public function userRegister(Request $request, Response $response)
    {
        $pdo       = $this->container->get(PDO::class);
        $userModel = new UserModel($pdo);

        $settingsModel = new SettingsModel($pdo);

        $settings = $settingsModel->getLastSettings();

        if ($settings['register'] == false) {
            $responseData['data']['message'] = 'Register is not allowed';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 406);
            return $response;
        }

        $body = $request->getBody();
        $data = json_decode($body, true);

        // Validate data
        try {
            v::key('email', v::notEmpty())->assert($data);
            v::key('password', v::notEmpty())->assert($data);
            v::key('passwordConfirm', v::notEmpty())->assert($data);
            v::key('passwordConfirm', v::equals($data['password']))->assert($data);
        } catch (NestedValidationException $exception) {
            $responseData['data']['message'] = current($exception->getMessages());
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 406);
            return $response;
        }

        $email    = $data['email'];
        $password = $data['password'];

        // Hash the password before storing it in the database
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);


        // Check if the email is already taken
        $user = $userModel->findAll()->where('email', '=', $email)->execute('fetch');
        //ถ้ากำลังจะสมัคร แต่มี email แล้ว เป็นไปได้ว่าเคยสมัครด้วย google
        if ($user !== false && $user['email'] !== false) {

            //incase using google to login so this will set a password
            if ($user['password'] != '') {
                $responseData['data']['message'] = 'Email already exists';
                $response                        = MyResponseHandler::handleResponse($response, $responseData, 400);
                return $response;
            }

            // Update password to that email because already register with google
            $userModel
                ->update(['password' => $hashedPassword])
                ->where('email', '=', $email)
                ->execute();

            $responseData['data']['message'] = 'Update password successfully';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 202);
            return $response;

        }

        $mailerConfig = $this->container->get(SettingsInterface::class)->get('mailer');
        $mail         = $this->container->get(PHPMailer::class);
        $emailSender  = new EmailSender($mailerConfig, $mail);

        $confirmationCode = bin2hex(random_bytes(5));
        $confirmationLink = $_ENV["MYPATH"] . '/email-confirm/' . $confirmationCode;
        $subject          = 'Email Confimation';
        $body             = "Click the following link to confirm your email: <a href='$confirmationLink'>$confirmationLink</a>";

        if (!$emailSender->sendConfirmationEmail($email, $subject, $body, $confirmationCode)) {

            $responseData['data']['message'] = 'Unexpedted error on sending email';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
            return $response;

        }

        //if there is a new user just insert new data to database
        // Insert the user into the "users" table
        $user = $userModel->find(1)->andWhere("user_role", "=", "1")->execute();

        if ($user === false) {
            $responseData['data']['message'] = $userModel->getLastException();
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
            return $response;
        }
        if (empty($user)) {
            $insertData = ['email' => $email, 'password' => $hashedPassword, 'confirmation_code' => $confirmationCode, 'user_role' => 1];
        } else {
            $insertData = ['email' => $email, 'password' => $hashedPassword, 'confirmation_code' => $confirmationCode];
        }
        $userModel->insert($insertData)->execute();

        $responseData['data']['message'] = 'Confirmation Code has been sent to your email: ' . $email;
        $response                        = MyResponseHandler::handleResponse($response, $responseData, 201);
        return $response;
    }
    public function confirmEmail(Request $request, Response $response, $args)
    {
        $pdo       = $this->container->get(PDO::class);
        $userModel = new UserModel($pdo);

        $confirmationCode = $args['confirmationCode'];

        $resetConfirmationCode = bin2hex(random_bytes(5));
        // Process confirmation code and mark email as confirmed
        $user = $userModel->findAll()->where("confirmation_code", "=", $confirmationCode)->execute()[0] ?? null;

        if (!$user) {
            $responseData['data']['message'] = 'Invalid Confirmation Code, User not found';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 400);
            return $response;
        }

        if ($user['confirmed']) {
            $responseData['data']['message'] = 'This Email has already confirmed';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 200);
            return $response;
        }

        $updateData = ['confirmed' => true, 'confirmation_code' => $resetConfirmationCode];
        $userModel->update($updateData)->where('email', '=', $user['email'])->execute();

        $responseData['data']['message'] = 'Email has been confirmed';
        $response                        = MyResponseHandler::handleResponse($response, $responseData, 200);
        return $response;


    }
    public function userLogin(Request $request, Response $response)
    {
        $pdo = $this->container->get(PDO::class);

        $userModel = new UserModel($pdo);

        $data = json_decode($request->getBody(), true);

        //validate Input
        try {
            v::key('email', v::notEmpty())->assert($data);
            v::key('password', v::notEmpty())->assert($data);
            v::key('email', v::email())->assert($data);
        } catch (NestedValidationException $exception) {
            $responseData['data']['message'] = current($exception->getMessages());
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 406);
            return $response;
        }

        $email      = $data['email'];
        $password   = $data['password'];
        $rememberMe = $data['rememberMe'] ?? false;

        //get user from email
        $user = $userModel->findAll()->where('email', '=', $email)->execute('fetch');

        //if no record found
        //user have not confirmed yet
        // Check if the user exists and the password is correct.
        if ($user === false) {
            $responseData['data']['message'] = $userModel->getLastException() ?? 'User Not Found! Please Register';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 400);
            return $response;
        } else if (!$user['confirmed']) {
            $responseData['data']['message'] = 'Please confirmed your email <br> by the link that we have already sent';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 401);
            return $response;
        } else if (!password_verify($password, $user['password'])) {
            $responseData['data']['message'] = 'Invalid credentials';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 401);
            return $response;
        }

        // Generate a JWT token.
        $token = JwtTokenGenerator::generateJwtToken($user['id'], $user['email']);

        if ($rememberMe) { // Set the JWT token as a cookie
            $response = $this->setLoginCookie($response, $token);
        } else { // Set the JWT token as a session
            $_SESSION['jwt_token'] = $token;
        }

        $responseData['data']['message'] = 'Login Successfully';
        $responseData['data']['jwt']     = $token;
        $response                        = MyResponseHandler::handleResponse($response, $responseData);
        return $response;
    }
    public function userLoginWithGoogle(Request $request, Response $response, $args)
    {
        $pdo           = $this->container->get(PDO::class);
        $userModel     = new UserModel($pdo);
        $settingsModel = new SettingsModel($pdo);

        $queryParams  = $request->getQueryParams();
        $redirect_url = $queryParams['redirect_url'] ?? '';
        $settings     = $settingsModel->getLastSettings();
        $body         = $request->getParsedBody();

        $client = new Google_Client(['client_id' => $settings['client_id']]);

        $decoded = $client->verifyIdToken($body['credential']);

        $user = $userModel
            ->findAll()
            ->where("email", '=', $decoded['email'])
            ->orWhere("google_sub_id", '=', $decoded['sub'])
            ->execute('fetch');

        if (!$user) { //กรณี login โดยไม่เคยสมัคร
            if ($settings['register_with_google'] == false) {
                $responseData['data']['message'] = 'Register unavailable';
                return $this->responseOnGoogleLogin($response, $body['select_by'], $responseData, 409);
            }

            //กรณีเป็นการสมัครครั้งแรกเลย
            $user = $userModel->find(1)->andWhere("user_role", "=", "1")->execute();

            if ($user === false) {
                $responseData['data']['message'] = $userModel->getLastException();
                $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
                return $response;
            }
            if (empty($user)) {
                $insertData = ['email' => $decoded['email'], 'google_sub_id' => $decoded['sub'], 'confirmed' => true, 'user_role' => 1];
            } else {
                $insertData = ['email' => $decoded['email'], 'google_sub_id' => $decoded['sub'], 'confirmed' => true];

            }

            if ($userModel->insert($insertData)->execute() === false) {
                $responseData['data']['message'] = $userModel->getLastException();
                $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
                return $response;
            }

            $responseData['data']['jwt']     = $body['credential'];
            $responseData['data']['message'] = 'Register successfully! <br>Please reload the page again';

            $token    = JwtTokenGenerator::generateJwtToken($decoded['sub'], $decoded['email']);
            $response = $this->setLoginCookie($response, $token);

            return $this->responseOnGoogleLogin($response, $body['select_by'], $responseData, 201, 'admin');

        } else if ($user['email'] = $decoded['email'] && empty($user['google_sub_id'])) {  //กรณี เคยสมัครด้วย email ไว้แล้ว แต่login ด้วย google

            $updateData = ["google_sub_id" => $decoded['sub'], "confirmed" => true];
            if ($userModel->update($updateData)->where('email', '=', $decoded['email'])->execute() === false) {
                $responseData['data']['message'] = $userModel->getLastException();
                $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
                return $response;
            }

            $responseData['data']['message'] = 'Register ' . $decoded['email'] . ' with Google Acccount Successfully ';
            return $this->responseOnGoogleLogin($response, $body['select_by'], $responseData, 201, $redirect_url);

        }

        //กรณี มี email และ sub_id ที่ถูกต้อง ก็ gen token เลย
        $token    = JwtTokenGenerator::generateJwtToken($decoded['sub'], $decoded['email']);
        $response = $this->setLoginCookie($response, $token);

        $responseData['data']['message'] = 'Login Successfully';
        $responseData['data']['jwt']     = $token;

        return $this->responseOnGoogleLogin($response, $body['select_by'], $responseData, 200, $redirect_url, 'admin', );

    }
    private function responseOnGoogleLogin($response, $select_by, $responseData, $statusCode, $redirect_url, $path = 'login')
    {
        if ($select_by == "user") { //come from one tap

            $response = MyResponseHandler::handleResponse($response, $responseData, $statusCode);

        } else if ($select_by == "btn") { //click btn
            if ($path == 'login') {
                $firstStatusCode = substr($statusCode, 0, 1);
                ($firstStatusCode == '2' || $firstStatusCode == '3') ? $status = 'true' : $status = 'false';

                $url = '/login?message=' . $responseData['data']['message'] . '&status=' . $status;
            } else {

                if ($redirect_url == '') {

                    $url = '/admin/items/report';
                } else {
                    $url = $redirect_url;
                }
            }

            //status Code must always be 302 when redirect!
            $response = $response->withStatus(302)->withHeader('Location', $_ENV["MYPATH"] . $url);
        }

        return $response;

    }
    private function setLoginCookie(Response $response, string $token)
    {
        // Set the JWT token as a cookie
        $expirationTime = time() + $_ENV["EXPIRE_TIME_IN_SECS"]; // Set expiration time to 1 hour from now
        $expirationDate = gmdate('D, d M Y H:i:s T', $expirationTime);
        return $response
            ->withHeader('Content-Type', 'application/json')->withStatus(200)
            ->withHeader('Set-Cookie', "jwt_token= $token; Path=/; Expires=$expirationDate;");

    }
    public function userLogout(Request $request, Response $response)
    {

        $isBackendRequest = strpos($request->getHeaderLine('User-Agent'), "PostmanRuntime") !== false;

        if (
            (!isset($_COOKIE['jwt_token']) && empty($_COOKIE['jwt_token'])) &&
            (!isset($_SESSION['jwt_token']) && empty($_SESSION['jwt_token']))
        ) {

            if ($isBackendRequest) {
                $responseData['data']['message'] = 'You are already Logout';
                $response                        = MyResponseHandler::handleResponse($response, $responseData);
                return $response;
            } else {
                return $response->withHeader('Location', $_ENV["MYPATH"] . '/login')->withStatus(302);
            }
        }

        setcookie('jwt_token', '', time() - 1, '/');
        unset($_COOKIE['jwt_token']);
        unset($_SESSION['jwt_token']);

        if ($isBackendRequest) {
            $responseData['data']['message'] = 'Logout Succesfully';
            $response                        = MyResponseHandler::handleResponse($response, $responseData);
            return $response;
        } else {
            return $response->withHeader('Location', $_ENV["MYPATH"] . '/login')->withStatus(302);
        }


    }
    public function tokenValidation(Request $request, Response $response)
    {

        $cookies = $request->getCookieParams();

        if (isset($cookies['jwt_token']) || isset($_SESSION['jwt_token'])) {

            $cookieValue = $cookies['jwt_token'] ?? $_SESSION['jwt_token'];
            $decoded     = JWT::decode($cookieValue, new Key($_ENV['SECRET_KEY'], 'HS256'));

            $responseData['data']['jwt_token'] = $decoded;
            $response                          = MyResponseHandler::handleResponse($response, $responseData, 200);
        } else {
            $responseData['data']['message'] = 'Token not found';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 400);
        }
        return $response;
    }
    public function resetPassword(Request $request, Response $response)
    {
        $pdo       = $this->container->get(PDO::class);
        $userModel = new UserModel($pdo);

        $body = $request->getBody();
        $data = json_decode($body, true);

        //validate Input
        try {
            v::key('email', v::notEmpty())->assert($data);
            v::key('email', v::email())->assert($data);
        } catch (NestedValidationException $exception) {
            $responseData['data']['message'] = "Something wrong with input";
            $responseData['data']['error']   = current($exception->getMessages());
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 406);
            return $response;
        }

        $email = $data['email'];

        $user = $userModel
            ->findall()
            ->where("email", "=", $email)
            ->andWhere("password", "<>", "")
            ->execute();
        if (!$user) {
            $responseData['data']['message'] = 'Email have\'t been register. Please register';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 406);
            return $response;
        }


        $mail         = $this->container->get(PHPMailer::class);
        $mailerConfig = $this->container->get(SettingsInterface::class)->get('mailer');

        $mailer = new EmailSender($mailerConfig, $mail);

        $confirmationCode = bin2hex(random_bytes(5));

        $confirmationLink = $_ENV["MYPATH"] . '/resetPasswordConfirm/' . $confirmationCode;
        $subject          = 'Reset Password Confirmation';
        $body             = "Click the following link to reset password of your account: <a href='$confirmationLink'>$confirmationLink</a>";

        if (!$mailer->sendConfirmationEmail($email, $subject, $body, $confirmationCode)) {

            $responseData['data']['message'] = 'Unexpedted error on sending email';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 500);
            return $response;

        }

        // Update password to that email because already register with google
        $userModel->update(['reset_password_code' => $confirmationCode])->where('email', '=', $email)->execute();

        $responseData['data']['message'] = 'Reset Password Confirmation has been sent to your email: ' . $email;
        $response                        = MyResponseHandler::handleResponse($response, $responseData, 201);
        return $response;
    }
    public function resetPasswordConfirm(Request $request, Response $response, $args)
    {
        $pdo       = $this->container->get(PDO::class);
        $userModel = new UserModel($pdo);

        $body = $request->getBody();
        $data = json_decode($body, true);

        $email               = $data['email'];
        $password            = $data['password'];
        $passwordConfirm     = $data['passwordConfirm'];
        $passwordConfirmCode = $data['passwordConfirmCode'];


        //find user with reset password code and email
        $user = $userModel
            ->findAll()
            ->where('reset_password_code', '=', $passwordConfirmCode)
            ->andWhere('email', '=', $email)
            ->execute();


        if (!$user) {
            $responseData['data']['message'] = 'Invalid Confirmation Code';
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 400);
            return $response;
        }

        // Validate data
        try {
            v::email()->assert($email);
            v::notEmpty()->assert($password);
            v::equals($password ?? null)->assert($passwordConfirm);
        } catch (NestedValidationException $exception) {
            $responseData['data']['message'] = current($exception->getMessages());
            $response                        = MyResponseHandler::handleResponse($response, $responseData, 406);
            return $response;
        }

        $hashedPassword      = password_hash($password, PASSWORD_BCRYPT);
        $reset_password_code = bin2hex(random_bytes(5));

        // Process confirmation code and mark email as confirmed
        $updateDate = ['password' => $hashedPassword, 'reset_password_code' => $reset_password_code];
        $userModel
            ->update($updateDate)
            ->where('email', '=', $email)
            ->execute();

        $responseData['data']['message'] = 'Password has been changed';
        $response                        = MyResponseHandler::handleResponse($response, $responseData, 202);
        return $response;

    }
}