<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Icon;

class DropdownAction extends AbstractAction
{
    protected array $actions;

    /**
     * @param array<AbstractAction[]> $actions
     */
    public static function new(string $label, array $actions, ?Icon $icon = null): static
    {
        return (new static($label))
            ->setActions($actions)
            ->setIcon($icon);
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
