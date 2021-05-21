<?php

declare(strict_types=1);

namespace Lle\CruditBundle\EventListener;

use Lle\CruditBundle\Controller\CrudController;
use Lle\CruditBundle\Filter\FilterState;
use Lle\CruditBundle\Provider\ConfigProvider;
use Lle\CruditBundle\Provider\LayoutProvider;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class KernelFilterListener
{
    /** @var FilterState  */
    private $filterState;

    public function __construct( FilterState $filterState)
    {
        $this->filterState = $filterState;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $this->filterState->handleRequest($request);
    }
}
