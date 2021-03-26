<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface BrickResponseInterface
{
    public function isRedirect(): bool;

    public function handle(Request $request, Response $response): Response;
}
