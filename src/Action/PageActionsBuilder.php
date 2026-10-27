<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Action;

use Lle\CruditBundle\Dto\Action\AbstractAction;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Lle\CruditBundle\Dto\Action\EditAction;
use Lle\CruditBundle\Dto\Action\PrintAction;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Sorts the actions of the header of a page (brick/links) for its bar (PageActions): the hidden ones removed
 * (ActionResolver), the first primary one kept as such, the actions of the menu "More" by group, a DropdownAction
 * without any action shown removed. The parameters of a route: the id of the request (not for the way back to the
 * list), then those of its path, which win.
 * The actions of a line of a list the same way (buildRow(), RowActions), with the id of the line, which wins.
 */
class PageActionsBuilder
{
    public function __construct(
        protected ActionResolver $resolver,
        protected UrlGeneratorInterface $urlGenerator,
        protected RequestStack $requestStack,
    ) {
    }

    /**
     * @param iterable<AbstractAction> $actions
     */
    public function build(iterable $actions, ?object $resource = null): PageActions
    {
        $inline = [];
        /** @var array<string, array{label: ?string, domain: ?string, items: list<ActionView>}> $more */
        $more = ['' => ['label' => null, 'domain' => null, 'items' => []]];
        $hasPrimary = false;

        foreach ($actions as $action) {
            $view = $this->view($action, $resource, !$action->isDropdown() && !$hasPrimary);
            if ($view === null) {
                continue;
            }
            if ($action->isDropdown()) {
                $group = $action->getGroup() ?? '';
                $more[$group] ??= ['label' => $group, 'domain' => null, 'items' => []];
                $more[$group]['items'][] = $view;
                continue;
            }
            $hasPrimary = $hasPrimary || $view->primary;
            $inline[] = $view;
        }

        return new PageActions($inline, array_values(array_filter($more, static fn (array $group): bool => $group['items'] !== [])));
    }

    /**
     * @param iterable<AbstractAction> $actions
     */
    #[AsTwigFunction('crudit_row_actions')]
    public function buildRow(iterable $actions, object $resource, int|string|null $id = null): RowActions
    {
        $main = null;
        $others = [];
        foreach ($actions as $action) {
            $view = $this->view($action, $resource, false, $id, (string) $id);
            if ($view === null) {
                continue;
            }
            if ($main === null && $action instanceof EditAction) {
                $main = $view;
            } else {
                $others[] = $view;
            }
        }

        return new RowActions($main, $others);
    }

    /**
     * @param int|string|null $id the id of the line (buildRow()), null: the one of the request
     */
    protected function view(AbstractAction $action, ?object $resource, bool $canBePrimary = false, int|string|null $id = null, string $modalSuffix = ''): ?ActionView
    {
        $state = $this->resolver->resolve($action, $resource, $this->requestStack->getCurrentRequest());
        if (!$state->visible) {
            return null;
        }

        if ($action instanceof DropdownAction) {
            $groups = $this->groups($action, $resource, $id, $modalSuffix);

            return $groups === [] ? null : new ActionView($action, $state, groups: $groups, resource: $resource);
        }

        $params = $this->params($action, $id);

        return new ActionView(
            $action,
            $state,
            $this->url($action, $params),
            $params,
            $canBePrimary && $action->isPrimary(),
            modalSuffix: $modalSuffix,
            resource: $resource,
        );
    }

    /**
     * @return list<array{label: ?string, domain: ?string, items: list<ActionView>}> the groups of a DropdownAction (a string key: its
     *                                                               heading), without the actions hidden
     */
    protected function groups(DropdownAction $dropdown, ?object $resource, int|string|null $id = null, string $modalSuffix = ''): array
    {
        $groups = [];
        foreach ($dropdown->getActions() as $label => $actions) {
            $items = [];
            foreach ((array) $actions as $action) {
                $view = $this->view($action, $resource, false, $id, $modalSuffix);
                if ($view !== null) {
                    $items[] = $view;
                }
            }
            if ($items !== []) {
                $groups[] = ['label' => is_string($label) ? $label : null, 'domain' => $dropdown->getTranslationDomain(), 'items' => $items];
            }
        }

        return $groups;
    }

    /**
     * @return array<string, mixed>
     */
    protected function params(AbstractAction $action, int|string|null $id = null): array
    {
        // The way back to the list is not for the record (no ?id=… in its address)
        if ($action instanceof PrintAction || $action->isBack()) {
            return $action instanceof PrintAction ? [] : $action->getPath()->getParams();
        }
        $params = $action->getPath()->getParams();
        if ($id !== null && $id !== '') {
            // A line: its id wins over the parameters of the path, as before 2.0
            return [...$params, 'id' => $id];
        }
        $id = $this->requestStack->getCurrentRequest()?->attributes->get('id');

        // The header: the parameters of the path win, as before 2.0
        return $id !== null && $id !== '' ? ['id' => $id, ...$params] : $params;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function url(AbstractAction $action, array $params): ?string
    {
        if ($action instanceof PrintAction) {
            return null;
        }

        return $action->getUrl() ?? $this->urlGenerator->generate($action->getPath()->getRoute(), $params);
    }
}
