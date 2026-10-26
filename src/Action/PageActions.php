<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Action;

/**
 * The actions of the header of a page (brick/links), sorted (PageActionsBuilder):
 * - inline: in the bar, in their order, the primary one among them (at most one), the DropdownAction too;
 * - more: the actions of the menu "More" (setDropdown(true)), by group: those without group first.
 */
final class PageActions
{
    /**
     * @param list<ActionView>                                     $inline
     * @param list<array{label: ?string, items: list<ActionView>}> $more
     */
    public function __construct(
        public readonly array $inline = [],
        public readonly array $more = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->inline === [] && $this->more === [];
    }

    /**
     * @return list<ActionView> the actions of the bar that are not the primary one: in the menu "More" below md
     */
    public function getSecondary(): array
    {
        return array_values(array_filter($this->inline, static fn (ActionView $view): bool => !$view->primary));
    }

    /**
     * @return list<array{label: ?string, items: list<ActionView>}> the secondary actions as entries of the menu "More"
     *                                                               (below md): a DropdownAction is a group named
     *                                                               after it, with the actions of all its groups
     */
    public function getSecondaryGroups(): array
    {
        $groups = [];
        foreach ($this->getSecondary() as $view) {
            $groups[] = $view->isDropdown() && !$view->state->isDisabled()
                ? ['label' => $view->action->getLabel(), 'items' => $view->getGroupItems()]
                : ['label' => null, 'items' => [$view]];
        }

        return $groups;
    }

    /**
     * @return list<ActionView> the actions with a modal (setModal()), shown and usable: their dialog is in the page
     */
    public function getModals(): array
    {
        return self::withModal([...$this->inline, ...array_merge(...array_column($this->more, 'items'))]);
    }

    /**
     * @param list<ActionView> $views
     *
     * @return list<ActionView> those with a modal, shown and usable, those of the DropdownAction too
     */
    public static function withModal(array $views): array
    {
        $modals = [];
        foreach ($views as $view) {
            foreach ([$view, ...$view->getGroupItems()] as $item) {
                if ($item->action->getModal() !== null && !$item->state->isDisabled()) {
                    $modals[] = $item;
                }
            }
        }

        return $modals;
    }
}
