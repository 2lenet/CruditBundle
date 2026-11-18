<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\BrickResponse;

use Lle\CruditBundle\Contracts\BrickResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the status of the response: a form sent with errors is answered by a 422, as Turbo (and Symfony's own
 * controllers) expect. A 200 is not shown by Turbo, which asks for a redirect or an error.
 */
class StatusBrickResponse implements BrickResponseInterface
{
    public function __construct(private readonly int $status)
    {
    }

    public function isRedirect(): bool
    {
        return false;
    }

    public function handle(Request $request, Response $response): Response
    {
        return $response->setStatusCode($this->status);
    }
}
