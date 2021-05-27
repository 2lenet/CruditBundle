<?php

declare(strict_types=1);

namespace Lle\CruditBundle\EventListener;

use Lle\CruditBundle\Provider\LayoutProvider;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class KernelControllerListener
{

    /** @var Environment */
    private $twig;

    /** @var LayoutProvider */
    private $layoutProvider;

    public function __construct(
        Environment $twig,
        LayoutProvider $layoutProvider
    ) {
        $this->twig = $twig;
        $this->layoutProvider = $layoutProvider;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $this->twig->addGlobal('crudit', $this->layoutProvider->getLayout());
    }
}
