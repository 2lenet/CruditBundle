<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick;

use Lle\CruditBundle\Contracts\BrickInterface;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Resolver\RessourceResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractBasicBrickFactory implements BrickInterface
{
    /** @var RessourceResolver  */
    protected $ressourceResolver;

    /** @var RequestStack  */
    protected $requestStack;

    public function __construct(
        RessourceResolver $ressourceResolver,
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
        $this->ressourceResolver = $ressourceResolver;
    }

    protected function getRequest(): Request
    {
        $request = $this->requestStack->getMasterRequest();
        if ($request) {
            return $request;
        }
        throw new CruditException('current request not found');
    }
}
