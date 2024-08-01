<?php

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Contracts\ActionInterface;
use Lle\CruditBundle\Dto\Path;

class DropdownAction extends AbstractAction
{
    protected array $actions;

    /**
     * @param array|AbstractAction[] $actions
     */
    public static function new(string $label, array $actions): static
    {
        return (new static($label))
            ->setActions($actions);
    }

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function setActions(array $actions): static
    {
        $this->actions = $actions;

        return $this;
    }
}
