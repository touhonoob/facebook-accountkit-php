<?php

namespace Ingresse\Accountkit\Exception;

use RuntimeException;
use Exception;

class UnexpectedException extends RuntimeException
{
    /**
     * @param Exception $e
     */
    public function __construct(Exception $e = null)
    {
        parent::__construct(
            'Unexpected Error into Accountkit Request',
            500,
            $e
        );
    }
}
