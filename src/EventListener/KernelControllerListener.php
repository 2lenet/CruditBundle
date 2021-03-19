<?php

declare(strict_types=1);

namespace Lle\CruditBundle\EventListener;

use Lle\CruditBundle\Controller\CrudController;
use Lle\CruditBundle\Provider\ConfigProvider;
use Lle\CruditBundle\Provider\LayoutProvider;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class KernelControllerListener
{

    /** @var Environment */
    private $twig;

    /** @var LayoutProvider */
    private $layoutProvider;

    /** @var ConfigProvider  */
    private $configProvider;

    /** @var ControllerResolverInterface  */
    private $controllerResolver;

    public function __construct(
        Environment $twig,
        LayoutProvider $layoutProvider,
        ConfigProvider $configProvider,
        ControllerResolverInterface $controllerResolver
    ) {
        $this->twig = $twig;
        $this->layoutProvider = $layoutProvider;
        $this->configProvider = $configProvider;
        $this->controllerResolver = $controllerResolver;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $this->twig->addGlobal('crudit', $this->layoutProvider->getLayout());
        $controller = $event->getController();
        if (
            is_array($controller) &&
            get_class($controller[0]) === CrudController::class &&
            $event->getRequest()->attributes->has('ressource') &&
            $this->configProvider->getConfigurator($event->getRequest()->attributes->get('ressource'))
        ) {
            $configurator = $this->configProvider
                ->getConfigurator(
                    $event->getRequest()->attributes->get('ressource')
                );
            if ($configurator && \is_callable($configurator->getController() . '::' . $controller[1])) {
                $request->attributes->set('_controller', $configurator->getController() . '::' . $controller[1]);
                $newController = $this->controllerResolver->getController($request);
                if ($newController) {
                    $event->setController($newController);
                }
            }
        }
    }
}
