<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick;

use Lle\CruditBundle\Contracts\BrickResponseInterface;
use Lle\CruditBundle\Exception\CruditException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BrickResponseCollector
{
    /** @var BrickResponseInterface[] */
    private $responses = [];

    public function add(BrickResponseInterface $response): self
    {
        $this->responses[] = $response;

        return $this;
    }

    public function handle(Request $request, Response $response): Response
    {
        $redirect = [];
        foreach ($this->responses as $brickRespons) {
            if ($brickRespons->isRedirect()) {
                $redirect[] = $brickRespons;
            }
            $response = $brickRespons->handle($request, $response);
        }
        if (\count($redirect) > 1) {
            throw new CruditException('multi redirection');
        }

        return $response;
    }
}
