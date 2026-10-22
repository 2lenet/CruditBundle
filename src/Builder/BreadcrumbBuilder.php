<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Builder;

use Lle\CruditBundle\Contracts\LayoutElementInterface;
use Lle\CruditBundle\Dto\Layout\CategoryElement;
use Lle\CruditBundle\Dto\Layout\HeaderElement;
use Lle\CruditBundle\Dto\Layout\HorizontalSeparatorElement;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Layout\TitleElement;
use Lle\CruditBundle\Menu\ActiveMenuMatcher;
use Lle\CruditBundle\Routing\HomeUrl;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The breadcrumb of the page, from the menu of the user (decision of 20/10/2026), in the format of the Breadcrumb of
 * CruditUiBundle (list of {label, url?}, labels translated):
 * - the home page (the root of the application, under its base path);
 * - the entry of the menu of the current page and what holds it: the category or the header of its group, the title
 *   it is under (with its children or before its links), its sub-menu (texts), then the entry (a link), until a
 *   separator. Two levels of links at most, as the menu shows them (a link of a sub-menu, not its own children);
 * - the current page given by the caller (a record, "Edit"…).
 * The entry of the home page is not repeated, nor its group. Without an entry of the menu for the page, the home page and the current
 * page only.
 */
class BreadcrumbBuilder
{
    public function __construct(
        private MenuBuilder $menuBuilder,
        private ActiveMenuMatcher $activeMenuMatcher,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
        private HomeUrl $homeUrl,
    ) {
    }

    /**
     * @param list<array{label: string, url?: string}> $current the current page, after the entry of the menu
     *
     * @return list<array{label: string, url?: string}>
     */
    public function build(array $current = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $home = ['label' => $this->translator->trans('crudit.layout.breadcrumb_home', [], 'LleCruditBundle'), 'url' => $this->homeUrl->url()];
        $items = [];
        foreach ($request !== null ? $this->menuTrail($request) : [] as [$element, $button]) {
            $item = ['label' => $this->translator->trans((string) $element->getLibelle())];
            // A sub-menu is a button in the menu: a text here, even with a path
            $path = $element instanceof LinkElement && !$button ? $element->getPath() : null;
            if ($path !== null) {
                try {
                    $item['url'] = $this->urlGenerator->generate($path->getRoute(), $path->getParams());
                } catch (RoutingException) {
                    // A path of a provider that does not exist: a text, not an error on the page
                }
            }
            // The entry of the home page: the first item already, without its group
            if (($item['url'] ?? null) === $home['url']) {
                $items = [];
                break;
            }
            $items[] = $item;
        }

        return [$home, ...$items, ...$current];
    }

    /**
     * The breadcrumb of a page of a CRUD (TraitCrudController), from the end of its route: the record on its page
     * (`_show`), the record (a link to its page) then "Edit" (`_edit`), "New" (`_new`), nothing more on the list.
     *
     * @return list<array{label: string, url?: string}>
     */
    public function buildForCrud(string $record): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $route = (string) $request?->attributes->get('_route');
        if (!preg_match('/^(.+)_(show|edit|new)$/', $route, $matches)) {
            return $this->build();
        }

        $current = match ($matches[2]) {
            'show' => [['label' => $record]],
            'edit' => [
                $this->recordLink($record, $matches[1] . '_show', $request),
                ['label' => $this->translator->trans('crudit.action.edit', [], 'LleCruditBundle')],
            ],
            'new' => [['label' => $this->translator->trans('crudit.layout.breadcrumb_new', [], 'LleCruditBundle')]],
        };

        return $this->build($current);
    }

    /**
     * The record, a link to its page (the parameters of the route of the page, a prefix such as /{tenant} included);
     * without a page of the record (no route, other parameters), a text.
     *
     * @return array{label: string, url?: string}
     */
    private function recordLink(string $record, string $route, Request $request): array
    {
        /** @var array<string, mixed> $parameters */
        $parameters = $request->attributes->get('_route_params', []);
        try {
            return ['label' => $record, 'url' => $this->urlGenerator->generate($route, $parameters)];
        } catch (RoutingException) {
            return ['label' => $record];
        }
    }

    /**
     * The groups of the entry, then the entry: a category or a header heads a group, a title (with its children or
     * before its links) a group in it; a separator ends them.
     *
     * @return list<array{LinkElement|TitleElement|CategoryElement|HeaderElement, bool}> the elements, and whether each
     *                                                                                is the button of a sub-menu
     */
    private function menuTrail(Request $request): array
    {
        $group = null;
        $title = null;
        foreach ($this->menuBuilder->build('menu_side') as $element) {
            if ($element instanceof HorizontalSeparatorElement) {
                $group = $title = null;
            } elseif ($element instanceof CategoryElement || $element instanceof HeaderElement) {
                $group = $element;
                $title = null;
            } elseif ($this->activeMenuMatcher->isActive($element, $request)) {
                // An active title is in the trail by itself
                $heads = array_filter([$group, $element instanceof TitleElement ? null : $title]);

                return [...array_map(static fn ($head): array => [$head, false], array_values($heads)), ...$this->trail($element, $request)];
            } elseif ($element instanceof TitleElement) {
                $title = $element;
            }
        }

        return [];
    }

    /**
     * The element and its active descendants, down to the entry of the page: a link of a sub-menu ends it (the menu
     * does not show its children, it shows it as a link). A link with children above it is the button of its sub-menu.
     *
     * @return list<array{LinkElement|TitleElement|CategoryElement|HeaderElement, bool}>
     */
    private function trail(LayoutElementInterface $element, Request $request, bool $inSubMenu = false): array
    {
        $button = !$inSubMenu && $element instanceof LinkElement && $element->getChildren() !== [];
        $trail = $element instanceof LinkElement || $this->isHeading($element) ? [[$element, $button]] : [];
        if ($inSubMenu) {
            return $trail;
        }

        foreach ($element->getChildren() as $child) {
            if ($this->activeMenuMatcher->isActive($child, $request)) {
                return [...$trail, ...$this->trail($child, $request, $element instanceof LinkElement)];
            }
        }

        return $trail;
    }

    /**
     * @phpstan-assert-if-true TitleElement|CategoryElement|HeaderElement $element
     */
    private function isHeading(LayoutElementInterface $element): bool
    {
        return $element instanceof TitleElement || $element instanceof CategoryElement || $element instanceof HeaderElement;
    }
}
