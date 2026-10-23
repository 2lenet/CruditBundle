<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Builder;

use Lle\CruditBundle\Contracts\LayoutElementInterface;
use Lle\CruditBundle\Dto\Layout\AbstractLayoutElement;
use Lle\CruditBundle\Dto\Layout\CategoryElement;
use Lle\CruditBundle\Dto\Layout\HeaderElement;
use Lle\CruditBundle\Dto\Layout\HorizontalSeparatorElement;
use Lle\CruditBundle\Dto\Layout\TitleElement;
use Lle\CruditBundle\Registry\MenuRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The menu shown to the user, from the elements of the providers (MenuRegistry):
 * - the elements the user is not granted are removed, children included;
 * - a sub-menu or a title whose children are all removed is removed (a title with children labels them only);
 * - a heading (title, category, header) without children and with no item under it (an item, or a heading with
 *   children) is removed;
 * - a separator at the start, at the end or after another separator is removed.
 *
 * The elements of the providers are not changed: an element whose children are filtered is a copy (withChildren()).
 * The menu is built once per request for each navid (the menu and the breadcrumb use it): the providers (badges
 * counted in the database) and the voters run once; reset between two requests (kernel.reset). It is kept for the
 * token it was built with: a menu built before the token is set (an early listener, a sub-request) is built again
 * for the user, not kept with the rights of nobody.
 */
class MenuBuilder implements ResetInterface
{
    /** @var array<string, array{?TokenInterface, list<LayoutElementInterface>}> */
    private array $built = [];

    public function __construct(
        private MenuRegistry $menuRegistry,
        private AuthorizationCheckerInterface $authorizationChecker,
        private ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    /** @return LayoutElementInterface[] */
    public function build(string $navid): array
    {
        $token = $this->tokenStorage?->getToken();
        if (!isset($this->built[$navid]) || $this->built[$navid][0] !== $token) {
            $this->built[$navid] = [$token, $this->removeSeparators(
                $this->removeEmptyHeadings($this->filterGranted($this->menuRegistry->getElements($navid))),
            )];
        }

        return $this->built[$navid][1];
    }

    public function reset(): void
    {
        $this->built = [];
    }

    /**
     * @param LayoutElementInterface[] $elements
     *
     * @return list<LayoutElementInterface>
     */
    private function filterGranted(array $elements): array
    {
        $granted = [];
        foreach ($elements as $element) {
            if (!$this->isGranted($element)) {
                continue;
            }

            $children = $element->getChildren();
            if ($children !== []) {
                $grantedChildren = $this->filterGranted($children);
                if ($grantedChildren === []) {
                    continue;
                }
                // Compares the instances, not the count: a child can be a copy (a sub-menu without a refused link)
                if ($grantedChildren !== $children) {
                    $element = $element->withChildren($grantedChildren);
                }
            }

            $granted[] = $element;
        }

        return $granted;
    }

    private function isGranted(LayoutElementInterface $element): bool
    {
        if (!$element instanceof AbstractLayoutElement) {
            return true;
        }

        return $this->authorizationChecker->isGranted($element->getRole());
    }

    /**
     * @param list<LayoutElementInterface> $elements
     *
     * @return list<LayoutElementInterface>
     */
    private function removeEmptyHeadings(array $elements): array
    {
        $kept = [];
        foreach ($elements as $i => $element) {
            if ($this->isHeading($element) && $element->getChildren() === [] && !$this->isFollowedByItem($elements, $i)) {
                continue;
            }
            $kept[] = $element;
        }

        return $kept;
    }

    /**
     * A heading with children has items (category "Administration", then title "Users" with its links); a separator
     * ends the section, the heading before it has nothing under it.
     *
     * @param list<LayoutElementInterface> $elements
     */
    private function isFollowedByItem(array $elements, int $index): bool
    {
        $next = $elements[$index + 1] ?? null;
        if ($next === null || $next instanceof HorizontalSeparatorElement) {
            return false;
        }

        return !$this->isHeading($next) || $next->getChildren() !== [];
    }

    /**
     * @param list<LayoutElementInterface> $elements
     *
     * @return list<LayoutElementInterface>
     */
    private function removeSeparators(array $elements): array
    {
        $kept = [];
        foreach ($elements as $element) {
            if ($element instanceof HorizontalSeparatorElement) {
                $previous = $kept[\count($kept) - 1] ?? null;
                if ($previous === null || $previous instanceof HorizontalSeparatorElement) {
                    continue;
                }
            }
            $kept[] = $element;
        }

        while ($kept !== [] && $kept[\count($kept) - 1] instanceof HorizontalSeparatorElement) {
            array_pop($kept);
        }

        return $kept;
    }

    private function isHeading(LayoutElementInterface $element): bool
    {
        return $element instanceof TitleElement
            || $element instanceof CategoryElement
            || $element instanceof HeaderElement;
    }
}
