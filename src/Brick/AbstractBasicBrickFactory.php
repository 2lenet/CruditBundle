<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick;

use Lle\CruditBundle\Contracts\BrickInterface;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractBasicBrickFactory implements BrickInterface
{
    /** @var ResourceResolver  */
    protected $resourceResolver;

    /** @var RequestStack  */
    protected $requestStack;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
        $this->resourceResolver = $resourceResolver;
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
