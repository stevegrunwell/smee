<?php

namespace Smee\Exceptions;

use Exception;

class HookExistsException extends Exception
{
    public $hook;

    public function getHook()
    {
        return $this->hook;
    }
}
