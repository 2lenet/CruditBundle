<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Action;

/**
 * The actions of a line of a list (RowActions.vue of the crudit-v2 mockup), sorted (PageActionsBuilder::buildRow()):
 * - main: the first edition (EditAction), an icon button;
 * - menu: the others, in the menu of the line, in their order; a DropdownAction is a group named after it.
 */
final class RowActions
{
    /**
     * @param list<ActionView>                                     $others
     */
    public function __construct(
        public readonly ?ActionView $main = null,
        public readonly array $others = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->main === null && $this->others === [];
    }

    /**
     * @return list<array{label: ?string, items: list<ActionView>}> the entries of the menu of the line
     */
    public function getMenu(): array
    {
        return array_map(static fn (ActionView $view): array => $view->isDropdown() && !$view->state->isDisabled()
            ? ['label' => $view->action->getLabel(), 'items' => $view->getGroupItems()]
            : ['label' => null, 'items' => [$view]], $this->others);
    }

    /**
     * @return list<ActionView>
     */
    public function getModals(): array
    {
        return PageActions::withModal($this->main === null ? $this->others : [$this->main, ...$this->others]);
    }
}
