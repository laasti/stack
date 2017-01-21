<?php

namespace Laasti\Stack;

use Exception;

/**
 * Exception used when the execution method from the stack fails
 */
class StackException extends Exception
{
    public function __construct($message = 'No response generated by the middleware stack.')
    {
        parent::__construct($message);
    }
}
