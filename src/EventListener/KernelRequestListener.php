<?php

namespace Lle\CruditBundle\EventListener;

use Lle\CruditBundle\Service\NavigationStack;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class KernelRequestListener
{
    public function __construct(
        protected ParameterBagInterface $parameterBag,
        protected NavigationStack $navigationStack,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $secFetchDest = $request->headers->get('sec-fetch-dest');
        $accept = $request->headers->get('Accept', '');

        // A real page navigation sends Accept: text/html (all browsers).
        // sec-fetch-dest: document is preferred when available (modern browsers),
        // but falls back to the Accept header to support Safari < 16.4 and other clients.
        $isDocumentRequest = $secFetchDest === 'document'
            || ($secFetchDest === null && str_contains($accept, 'text/html'));

        if (
            !$isDocumentRequest
            || $request->isXmlHttpRequest()
            || $request->attributes->get('_stateless') === true
        ) {
            return;
        }

        $referer = $request->headers->get('referer');
        $requestUri = $request->getUri();

        if ($referer) {
            /** @var array $ignoredRoutes */
            $ignoredRoutes = $this->parameterBag->get('lle_crudit.ignore_referer_routes');
            if (array_any($ignoredRoutes, fn($ignoredRoute) => (bool)preg_match($ignoredRoute, $referer))) {
                return;
            }
        }

        // If the current URL is already the last stack entry, the user navigated back — pop it.
        // Otherwise, push the referer (the page we just came from) onto the stack.
        if (!$this->navigationStack->removeIfLast($requestUri)) {
            if ($request->getMethod() !== 'POST' && $referer && $referer !== $requestUri) {
                $this->navigationStack->push($referer);
            }
        }
    }
}
