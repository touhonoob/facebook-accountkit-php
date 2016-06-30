<?php

namespace Ingresse\Accountkit\Exception;

use RuntimeException;
use Exception;

class VerifyException extends RuntimeException
{
    /**
     * @param Exception $e
     */
    public function __construct(Exception $e = null)
    {
        parent::__construct('Accountkit Validation Unsuccessful', 400, $e);
    }
}