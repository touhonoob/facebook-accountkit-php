<?php

namespace Ingresse\Accountkit\Exception;

use RuntimeException;
use Exception;

class RequestException extends RuntimeException
{
    /**
     * @param Exception $e
     */
    public function __construct(Exception $e = null)
    {
        parent::__construct('Request was not done properly', 500, $e);
    }
}