<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TemplateBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Symfony\Component\HttpFoundation\Request;

class TemplateConfig extends AbstractBrickConfig
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
