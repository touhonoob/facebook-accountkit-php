<?php

namespace Ingresse\Accountkit\Exception;

use RuntimeException;
use Exception;

class ResponseFieldException extends RuntimeException
{
    /**
     * @param string    $fileNotFound
     * @param Exception $e
     */
    public function __construct($fieldNotFound, Exception $e = null)
    {
        parent::__construct(
            sprintf('Response Field Not Found - %s', $fieldNotFound),
            500,
            $e
        );
    }
}
