<?php

namespace App\Exceptions;

use Exception;

class UnknownConnectorNameException extends Exception
{
    public function __construct(string $connectorName, ?\Throwable $previous = null)
    {
        $message = sprintf('Unknown connector name: %s', $connectorName);

        parent::__construct($message, 0, $previous);
    }
}
