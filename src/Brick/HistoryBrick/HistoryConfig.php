<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\HistoryBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;

class HistoryConfig extends AbstractBrickConfig
{
    public static function new(): self
    {
        return new self();
    }
}
