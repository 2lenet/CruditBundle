<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Icon;

use Lle\CruditBundle\Contracts\LayoutElementInterface;
use Lle\CruditBundle\Registry\MenuRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\UX\Icons\IconFinderInterface;

/**
 * Icons of the menu for `ux:icons:lock` and the cache warmer of Symfony UX Icons: the Iconify names (`prefix:name`)
 * given to the elements of the menu providers, which the finder of the Twig templates does not see (they are in PHP).
 * The logical names (`ui_icon('home')`) are already found by the finder of CruditUiBundle.
 *
 * It runs without a request nor a user: a provider that fails there is logged and skipped, the command goes on.
 */
class MenuIconFinder implements IconFinderInterface
{
    public function __construct(
        private MenuRegistry $menuRegistry,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @return list<string>
     */
    public function icons(): array
    {
        try {
            $elements = $this->menuRegistry->getElements('menu_side');
        } catch (\Throwable $exception) {
            $this->logger->warning('The icons of the menu cannot be found: {message}', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return [];
        }

        return array_values(array_unique($this->collect($elements)));
    }

    /**
     * @param LayoutElementInterface[] $elements
     *
     * @return list<string>
     */
    private function collect(array $elements): array
    {
        $icons = [];
        foreach ($elements as $element) {
            $icon = method_exists($element, 'getIcon') ? $element->getIcon() : null;
            if ($icon !== null && str_contains($icon->getIcon(), ':')) {
                $icons[] = $icon->getIcon();
            }
            $icons = [...$icons, ...$this->collect($element->getChildren())];
        }

        return $icons;
    }
}
