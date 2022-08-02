<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Provider;

use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\BrickInterface;

class BrickProvider
{
    private iterable $bricks;

    public function __construct(iterable $bricks)
    {
        $this->bricks = $bricks;
    }

    public function getBrick(BrickConfigInterface $brickConfigurator): ?BrickInterface
    {
        foreach ($this->bricks as $brick) {
            if ($brick->support($brickConfigurator)) {
                return $brick;
            }
        }
        return null;
    }
}
