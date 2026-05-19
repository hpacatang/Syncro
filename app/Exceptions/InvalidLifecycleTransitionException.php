<?php

namespace App\Exceptions;

use Exception;

class InvalidLifecycleTransitionException extends Exception
{
    public function __construct(string $message = 'Invalid lifecycle transition.')
    {
        parent::__construct($message);
    }
}
