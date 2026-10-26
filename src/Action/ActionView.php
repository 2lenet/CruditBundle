<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Action;

use Lle\CruditBundle\Dto\Action\AbstractAction;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Lle\CruditBundle\Dto\Action\PrintAction;

/**
 * An action ready to be rendered (PageActionsBuilder): its state, its address and the parameters of its route, and
 * for a DropdownAction its groups.
 */
final class ActionView
{
    /**
     * @param array<string, mixed>                                $params
     * @param list<array{label: ?string, items: list<ActionView>}> $groups the groups of a DropdownAction
     */
    public function __construct(
        public readonly AbstractAction $action,
        public readonly ActionState $state,
        public readonly ?string $url = null,
        public readonly array $params = [],
        public readonly bool $primary = false,
        public readonly array $groups = [],
        public readonly string $modalSuffix = '',
    ) {
    }

    /**
     * The variant of its Button: primary for the primary action; no colour of danger on a disabled action (decision
     * of 20/10), else its own.
     */
    public function getVariant(): string
    {
        $variant = $this->action->getVariant();

        return match (true) {
            $this->primary => 'primary',
            $this->state->isDisabled() && str_starts_with($variant, 'danger') => 'secondary',
            default => $variant,
        };
    }

    public function isDropdown(): bool
    {
        return $this->action instanceof DropdownAction;
    }

    public function isPrint(): bool
    {
        return $this->action instanceof PrintAction;
    }

    /**
     * The id of its modal (setModal()), as before 2.0: modal_<id of the action>, followed by the id of the line in a
     * list.
     */
    public function getModalId(): string
    {
        return 'modal_' . $this->action->getId() . $this->modalSuffix;
    }

    /**
     * @return list<ActionView> the actions of all its groups (a DropdownAction)
     */
    public function getGroupItems(): array
    {
        return array_merge(...array_column($this->groups, 'items'));
    }
}
