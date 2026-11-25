<?php

namespace phpunit\Unit\Test\Fixtures;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SampleController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    public function guardedByAttribute(): Response
    {
        return new Response();
    }

    public function guardedByCall(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return new Response();
    }

    public function guardedByHelper(): Response
    {
        $this->checkAccess();

        return new Response();
    }

    public function guardedByIsGranted(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        return new Response();
    }

    public function guardedByThrow(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return new Response();
    }

    public function displayOnlyIsGranted(): Response
    {
        $extra = $this->isGranted('ROLE_ADMIN') ? 'more' : 'less';

        return new Response($extra);
    }

    public function commentedOutCheck(): Response
    {
        // $this->denyAccessUnlessGranted('ROLE_ADMIN');
        /* $this->denyAccessUnlessGranted('ROLE_ADMIN'); */

        return new Response();
    }

    #[IsGranted('ROLE_USER')]
    public function trivialAttribute(): Response
    {
        return new Response();
    }

    public function trivialCall(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return new Response();
    }

    public function unguarded(): Response
    {
        return new Response();
    }

    public function unguardedWithLoopingHelper(): Response
    {
        $this->loopA();

        return new Response();
    }

    private function checkAccess(): void
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    }

    private function loopA(): void
    {
        $this->loopB();
    }

    private function loopB(): void
    {
        $this->loopA();
    }
}

#[IsGranted('ROLE_ADMIN')]
class GuardedClassController
{
    public function __invoke(): Response
    {
        return new Response();
    }
}

class OpenInvokableController
{
    public function __invoke(): Response
    {
        return new Response();
    }
}
