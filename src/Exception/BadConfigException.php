<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Exception;

use Throwable;

class BadConfigException extends CruditException
{
    /**
     * BadConfigException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Crudit configuration error: %s', $message), $code, $previous);
    }
}
