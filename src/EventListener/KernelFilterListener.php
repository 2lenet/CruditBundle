<?php

declare(strict_types=1);

namespace Lle\CruditBundle\EventListener;

use Lle\CruditBundle\Filter\FilterState;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class KernelFilterListener
{
    private FilterState $filterState;

    public function __construct(FilterState $filterState)
    {
        $this->filterState = $filterState;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $this->filterState->handleRequest($request);
    }
}
