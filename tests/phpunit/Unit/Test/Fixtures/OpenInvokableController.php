<?php

namespace phpunit\Unit\Test\Fixtures;

use Symfony\Component\HttpFoundation\Response;

class OpenInvokableController
{
    public function __invoke(): Response
    {
        return new Response();
    }
}
