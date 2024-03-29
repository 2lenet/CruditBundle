<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\BrickResponse;

use Lle\CruditBundle\Contracts\BrickResponseInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectBrickResponse implements BrickResponseInterface
{
    private string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function isRedirect(): bool
    {
        return true;
    }

    public function handle(Request $request, Response $response): Response
    {
        return new RedirectResponse($this->url);
    }
}
