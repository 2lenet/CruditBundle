<?php

namespace phpunit\Unit\Test\Fixtures;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class GuardedChildController extends UnguardedParentController
{
}
