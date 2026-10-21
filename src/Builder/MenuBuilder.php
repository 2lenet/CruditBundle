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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The menu shown to the user, from the elements of the providers (MenuRegistry):
 * - the elements the user is not granted are removed, children included;
 * - a sub-menu whose children are all removed is removed;
 * - a heading (title, category, header) with no item under it is removed;
 * - a separator at the start, at the end or after another separator is removed.
 *
 * The elements of the providers are not changed: an element whose children are filtered is a copy (withChildren()).
 */
class MenuBuilder
{
    public function __construct(
        private MenuRegistry $menuRegistry,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /** @return LayoutElementInterface[] */
    public function build(string $navid): array
    {
        $elements = $this->filterGranted($this->menuRegistry->getElements($navid));

        return $this->removeSeparators($this->removeEmptyHeadings($elements));
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
                // Empty sub-menu: removed. Empty title: kept, removeEmptyHeadings() decides.
                if ($grantedChildren === [] && !$element instanceof TitleElement) {
                    continue;
                }
                if (\count($grantedChildren) !== \count($children) && $element instanceof AbstractLayoutElement) {
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

    /** @param list<LayoutElementInterface> $elements */
    private function isFollowedByItem(array $elements, int $index): bool
    {
        $next = $elements[$index + 1] ?? null;

        return $next !== null && !$this->isHeading($next) && !$next instanceof HorizontalSeparatorElement;
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
