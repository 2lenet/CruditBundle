<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\BrickResponse;

use Lle\CruditBundle\Contracts\BrickResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FlashBrickResponse implements BrickResponseInterface
{
    public const SUCCESS = 'success';
    public const ERROR = 'danger';

    /** @var string  */
    private $type;

    /** @var string  */
    private $message;

    public function __construct(string $type, string $message)
    {
        $this->type = $type;
        $this->message = $message;
    }

    public function isRedirect(): bool
    {
        return false;
    }

    public function handle(Request $request, Response $response): Response
    {
        if (method_exists($request->getSession(), 'getFlashBag')) {
            $request->getSession()->getFlashBag()->add($this->type, $this->message);
        }
        return $response;
    }
}
