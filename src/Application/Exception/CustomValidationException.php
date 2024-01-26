<?php

namespace App\Application\Exception;

use Respect\Validation\Exceptions\NestedValidationException;

class CustomValidationException extends NestedValidationException
{
    protected $messages;
    // public function __construct(string $messages, $id = null, $code = null, $param = null, $subParam = null, $template = null, $value = null)
    // {
    //     $this->messages = $messages;
    //     parent::__construct($id, $code, $param, $subParam, $template, $value);
    //     $this->reportError($value);
    // }
    public function __construct($message = "Custom Exception", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getMessage()
    {
        return $this->messages;
    }

}