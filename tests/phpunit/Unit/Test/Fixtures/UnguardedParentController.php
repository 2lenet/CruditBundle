<?php

namespace phpunit\Unit\Test\Fixtures;

use Symfony\Component\HttpFoundation\Response;

class UnguardedParentController
{
    public function inherited(): Response
    {
        return new Response();
    }
}
