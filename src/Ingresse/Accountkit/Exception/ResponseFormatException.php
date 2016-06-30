<?php

namespace Ingresse\Accountkit\Exception;

use RuntimeException;
use Exception;

class ResponseFormatException extends RuntimeException
{
    /**
     * @param string    $fileNotFound
     * @param Exception $e
     */
    public function __construct(Exception $e = null)
    {
        parent::__construct('Unexpected Response Format', 500, $e);
    }
}
