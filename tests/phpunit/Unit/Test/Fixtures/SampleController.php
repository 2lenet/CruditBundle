<?php

namespace phpunit\Unit\Test\Fixtures;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SampleController extends AbstractController
{
    private const string ROLE_USER = 'ROLE_USER';

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

    public function guardedInsideTry(): Response
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            return new Response();
        } catch (AccessDeniedException) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }
    }

    public function conditionalCheckInsideTry(): Response
    {
        try {
            if (random_int(0, 1) === 1) {
                $this->denyAccessUnlessGranted('ROLE_ADMIN');
            }

            return new Response();
        } catch (AccessDeniedException) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }
    }

    public function throwsWhenGranted(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return new Response();
    }

    public function negatedWithoutDenial(): Response
    {
        $extra = 'less';
        if (!$this->isGranted('ROLE_ADMIN')) {
            $extra = 'none';
        }

        return new Response($extra);
    }

    public function checkInsideNeverCalledArrowFunction(): Response
    {
        $check = fn () => $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return new Response(gettype($check));
    }

    public function checkInIfWithoutBraces(): Response
    {
        // phpcs:ignore Generic.ControlStructures.InlineControlStructure -- the point of this fixture
        if (random_int(0, 1) === 1) $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return new Response();
    }

    public function checkBehindShortCircuit(): Response
    {
        random_int(0, 1) === 1 && $this->checkAccessAndReturnTrue();

        return new Response();
    }

    public function checkBehindTernary(): Response
    {
        random_int(0, 1) === 1 ? $this->checkAccessAndReturnTrue() : null;

        return new Response();
    }

    public function trivialConstantCheck(): Response
    {
        $this->denyAccessUnlessGranted(self::ROLE_USER);

        return new Response();
    }

    public function trivialVoterConstantCheck(): Response
    {
        $this->denyAccessUnlessGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY);

        return new Response();
    }

    public function conditionalCheck(): Response
    {
        if (random_int(0, 1) === 1) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        return new Response();
    }

    public function conditionalHelper(): Response
    {
        if (random_int(0, 1) === 1) {
            $this->checkAccess();
        }

        return new Response();
    }

    public function displayIsGrantedWithUnrelatedThrow(): Response
    {
        $extra = $this->isGranted('ROLE_ADMIN') ? 'more' : 'less';
        if (random_int(0, 1) === 1) {
            throw $this->createNotFoundException();
        }

        return new Response($extra);
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

    private function checkAccessAndReturnTrue(): bool
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return true;
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
