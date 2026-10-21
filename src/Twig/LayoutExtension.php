<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Configuration of the layout (`lle_crudit.layout`): logo, favicon, version and links of the footer. Read by the
 * fragments of the layout (sidebar/_logo, footer/), which the blocks brand, favicon and footer replace.
 */
class LayoutExtension extends AbstractExtension
{
    /**
     * @param array{
     *     logo: ?string,
     *     logo_dark: ?string,
     *     logo_small: ?string,
     *     favicon: ?string,
     *     version: ?string,
     *     release_date: ?string,
     *     footer_links: list<array{label: string, route: ?string, route_params: array<string, mixed>, url: ?string}>,
     * } $layout
     */
    public function __construct(
        protected array $layout,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('crudit_layout', [$this, 'layout'])];
    }

    /**
     * @return array<string, mixed>
     */
    public function layout(): array
    {
        return $this->layout;
    }
}
