<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Exception;

use Throwable;

class BadConfigException extends CruditException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct("Crudit configuration error: " . $message, $code, $previous);
    }
}
