<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\LinksBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Dto\Action\ListAction;

class LinksConfig extends AbstractBrickConfig
{
    /** @var array  */
    protected $actions = [];

    /** @var array */
    protected $options = [];

    /** @var bool  */
    protected $back = false;

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function addAction(ListAction $action): self
    {
        $this->actions[] = $action;
        return $this;
    }

    public function addBack(): self
    {
        $this->back = true;
        return $this;
    }

    public function hasBack(): bool
    {
        return $this->back;
    }

    /** @return ListAction[] */
    public function getActions(): array
    {
        return $this->actions;
    }
}
