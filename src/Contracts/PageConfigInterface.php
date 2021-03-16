<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Path;
use Symfony\Component\HttpFoundation\Request;

interface PageConfigInterface
{
    public function getBrickConfigs(Request $request): iterable;
}
