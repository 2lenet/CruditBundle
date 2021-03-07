<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Provider;

use Lle\CruditBundle\Contracts\BrickConfiguratorInterface;
use Lle\CruditBundle\Contracts\BrickInterface;

class BrickProvider
{
    /** @var iterable */
    private $bricks;

    public function __construct(iterable $bricks)
    {
        $this->bricks = $bricks;
    }

    public function getBrick(BrickConfiguratorInterface $brickConfigurator): ?BrickInterface
    {
        foreach ($this->bricks as $brick) {
            if ($brick->support($brickConfigurator)) {
                return $brick;
            }
        }
        return null;
    }
}
