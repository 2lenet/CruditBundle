<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Action;

/**
 * The actions of a line of a list (RowActions.vue of the crudit-v2 mockup), sorted (PageActionsBuilder::buildRow()):
 * - inline: shown, in their order: the edition (EditAction), and the actions setDropdown(false);
 * - others: the others, in the menu of the line, in their order; a DropdownAction is a group named after it.
 */
final class RowActions
{
    /**
     * @param list<ActionView> $inline
     * @param list<ActionView> $others
     * @param string           $entityLabel the line, in a text (%entity%): ActionView::labelOf()
     */
    public function __construct(
        public readonly array $inline = [],
        public readonly array $others = [],
        public readonly string $entityLabel = '',
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->inline === [] && $this->others === [];
    }

    /**
     * @return list<array{label: ?string, domain: ?string, items: list<ActionView>}> the entries of the menu of the line
     */
    public function getMenu(): array
    {
        return array_map(static fn (ActionView $view): array => $view->isDropdown() && !$view->state->isDisabled()
            ? ['label' => $view->action->getLabel(), 'domain' => $view->action->getTranslationDomain(), 'items' => $view->getGroupItems()]
            : ['label' => null, 'domain' => null, 'items' => [$view]], $this->others);
    }

    /**
     * @return list<ActionView>
     */
    public function getModals(): array
    {
        return PageActions::withModal([...$this->inline, ...$this->others]);
    }
}
