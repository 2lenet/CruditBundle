<?php

namespace Lle\CruditBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class KernelRequestListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->getRequest()->headers->has('referer')) {
            $session = $event->getRequest()->getSession();
            $session->remove('lle_crudit_referer');

            return;
        }

        $referer = $event->getRequest()->headers->get('referer');
        $host = $event->getRequest()->headers->get('host');
        $requestUri = $event->getRequest()->getUri();
        if ($referer && !str_ends_with($referer, $host . $requestUri)) {
            $session = $event->getRequest()->getSession();
            $session->set('lle_crudit_referer', $referer);
        }
    }
}
