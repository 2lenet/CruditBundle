<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Exception;

use Throwable;

class BadConfigException extends CruditException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Crudit configuration error: %s', $message), $code, $previous);
    }
}
