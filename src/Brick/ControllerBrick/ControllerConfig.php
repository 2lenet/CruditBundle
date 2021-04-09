<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ControllerBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;

class ControllerConfig extends AbstractBrickConfig
{
    /** @var array  */
    private $options = [];

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function getConfig(): array
    {
        return $this->options;
    }
}
