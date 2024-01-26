<?php

namespace App\Application\Helpers;


use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Application\Settings\SettingsInterface;

class EmailSender
{
    private array $mailerConfig;
    private PHPMailer $mailer;

    public function __construct(array $mailerConfig, PHPMailer $mailer)
    {
        $this->mailerConfig = $mailerConfig;
        $this->mailer       = $mailer;
    }

    public function sendConfirmationEmail($email, $subject, $body, $confirmationCode)
    {
        try {

            $from_email = $this->mailerConfig['from']['email'];
            $from_name  = $this->mailerConfig['from']['name'];

            $this->configureMailer($from_email, $from_name, $subject, $body);
            $this->mailer->addAddress($email);
            $this->mailer->send();

            return true;
        } catch (Exception $e) {
            // Log or handle the exception as needed
            return false;
        }
    }

    private function configureMailer($from_email, $from_name, $subject, $body)
    {
        $this->mailer->setFrom($from_email, $from_name);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = $subject;
        $this->mailer->Body    = $body;
    }
}
