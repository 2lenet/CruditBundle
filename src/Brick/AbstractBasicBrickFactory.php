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
        $request = $this->requestStack->getMainRequest();
        if ($request) {
            return $request;
        }
        throw new CruditException('current request not found');
    }

    public function getRequestParametersScop(): array
    {
        return [];
    }

    protected function getRequestParameters(): array
    {
        $parameters = [];
        foreach ($this->getRequestParametersScop() as $keyName) {
            if ($this->getRequest()->get($keyName)) {
                $parameters[$keyName] = $this->getRequest()->get($keyName);
            }
        }
        return $parameters;
    }
}
