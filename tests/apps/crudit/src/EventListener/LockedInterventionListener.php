<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Intervention;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\Exception\LogicException;

/**
 * Lot 3.4.c : un listener du workflow qui refuse une transition que can() autorise (l'intervention INT-LOCKED est
 * verrouillée) : la route répond 409, pas une page d'erreur.
 */
#[AsEventListener(event: 'workflow.intervention.transition')]
class LockedInterventionListener
{
    public function __invoke(TransitionEvent $event): void
    {
        $subject = $event->getSubject();
        if ($subject instanceof Intervention && 'INT-LOCKED' === $subject->getReference()) {
            throw new LogicException('INT-LOCKED is locked.');
        }
    }
}
