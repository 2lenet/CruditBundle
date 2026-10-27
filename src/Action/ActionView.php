<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Action;

use Lle\CruditBundle\Dto\Action\AbstractAction;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Lle\CruditBundle\Dto\Action\PrintAction;

/**
 * An action ready to be rendered (PageActionsBuilder): its state, its address and the parameters of its route, the
 * resource it is for (the record, the line: an ItemAction is one instance for all the lines, so the templates read
 * this one, not getResource() of the action), and for a DropdownAction its groups.
 */
final class ActionView
{
    /**
     * @param array<string, mixed>                                $params
     * @param list<array{label: ?string, domain: ?string, items: list<ActionView>}> $groups the groups of a DropdownAction
     */
    public function __construct(
        public readonly AbstractAction $action,
        public readonly ActionState $state,
        public readonly ?string $url = null,
        public readonly array $params = [],
        public readonly bool $primary = false,
        public readonly array $groups = [],
        public readonly string $modalSuffix = '',
        public readonly ?object $resource = null,
    ) {
    }

    /**
     * The variant of its Button: primary for the primary action only (one per page: setVariant('primary') on another
     * one gives secondary); no colour of danger on a disabled action (decision of 20/10), else its own.
     */
    public function getVariant(): string
    {
        $variant = $this->action->getVariant();

        return match (true) {
            $this->primary => 'primary',
            $variant === 'primary', $this->state->isDisabled() && str_starts_with($variant, 'danger') => 'secondary',
            default => $variant,
        };
    }

    /**
     * The element it is for, in a text (%entity%): labelOf() with the id of its route.
     */
    public function getEntityLabel(): string
    {
        return self::labelOf($this->resource, $this->params['id'] ?? null);
    }

    /**
     * An element in a text (%entity%): its __toString(), else "#<id>", else empty.
     */
    public static function labelOf(?object $resource, mixed $id = null): string
    {
        if ($resource instanceof \Stringable) {
            return (string) $resource;
        }

        return is_scalar($id) && $id !== '' ? '#' . $id : '';
    }

    /**
     * A DropdownAction (a button with its menu); not to mix up with isDropdown() of an action, in a menu.
     */
    public function isDropdownAction(): bool
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
