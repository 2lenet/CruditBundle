<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * `crudit_search()`: what the layout needs to show the global search (the button of the header and the command
 * palette), or null when it is disabled (`lle_crudit.search.enabled`) or when the project has not imported the route of
 * the bundle yet (UPGRADE-2.0.md): the pages must not fail for a search that cannot answer.
 */
class SearchExtension extends AbstractExtension
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly bool $enabled,
        private readonly int $minLength,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('crudit_search', $this->search(...))];
    }

    /**
     * @return array{url: string, min_length: int}|null
     */
    public function search(): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $url = $this->router->generate('lle_crudit_search');
        } catch (RouteNotFoundException) {
            return null;
        }

        return ['url' => $url, 'min_length' => $this->minLength];
    }
}
