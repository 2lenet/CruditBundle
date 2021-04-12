<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\BrickView;

interface BrickInterface
{
    public function support(BrickConfigInterface $brickConfigurator): bool;

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView;

    public function getRequestParametersScop(): array;
}
