<?php

namespace App\Exceptions;

class InvalidSignatureException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Invalid signature");
    }
}
