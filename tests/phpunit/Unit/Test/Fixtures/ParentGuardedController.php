<?php

namespace phpunit\Unit\Test\Fixtures;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ParentGuardedController
{
    public function inherited(): Response
    {
        return new Response();
    }
}
