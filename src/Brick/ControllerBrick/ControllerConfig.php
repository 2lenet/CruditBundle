<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ControllerBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Symfony\Component\HttpFoundation\Request;

class ControllerConfig extends AbstractBrickConfig
{
    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function getConfig(Request $request): array
    {
        return $this->options;
    }
}
