<?php

namespace Lle\CruditBundle\EventListener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

// This listener allows you to manage your browsing history.
class KernelRequestListener
{
    public function __construct(
        protected ParameterBagInterface $parameterBag,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Ignore AJAX calls and referrers not related to documents
        if (
            $event->getRequest()->isXmlHttpRequest()
            || $event->getRequest()->attributes->get('_stateless') === true
            || $event->getRequest()->headers->get('sec-fetch-dest') !== 'document'
        ) {
            return;
        }

        $referer = $event->getRequest()->headers->get('referer');
        $requestUri = $event->getRequest()->getUri();
        $requestMethod = $event->getRequest()->getMethod();

        if ($referer) {
            // Ignore routes defined in the configuration
            /** @var array $ignoredRoutes */
            $ignoredRoutes = $this->parameterBag->get('lle_crudit.ignore_referer_routes');
            if (array_any($ignoredRoutes, fn($ignoredRoute) => (bool)preg_match($ignoredRoute, $referer))) {
                return;
            }
        }

        $session = $event->getRequest()->getSession();
        $cruditReferer = json_decode($session->get('lle_crudit_referers', ''), true);

        // Remove the last referer if it's the current one
        if ($cruditReferer && end($cruditReferer) === $requestUri) {
            array_pop($cruditReferer);

            $session->set('lle_crudit_referers', json_encode($cruditReferer));
        } elseif (
            $requestMethod !== 'POST'
            && $referer
            && (!$cruditReferer || end($cruditReferer) !== $referer)
            && $referer !== $requestUri
        ) {
            $cruditReferer[] = $referer;

            // Keep only the last 20 referers
            while (count($cruditReferer) > 20) {
                array_shift($cruditReferer);
            }

            $session->set('lle_crudit_referers', json_encode($cruditReferer));
        }
    }
}
