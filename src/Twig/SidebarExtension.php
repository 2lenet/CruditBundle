<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * State of the menu chosen by the user, remembered in a cookie by the sidebar controller: the layout puts it in the
 * `data-sidebar` attribute of the shell, so the page arrives in the right state (full menu or rail), without a jump.
 * `auto` (no choice): the rail between 1024 and 1279 px, the full menu above. Like the theme of CruditUiBundle.
 */
class SidebarExtension extends AbstractExtension
{
    public const string COOKIE = 'crudit_sidebar';

    public const array MODES = ['auto', 'collapsed', 'expanded'];

    public function __construct(
        protected RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('crudit_sidebar', [$this, 'sidebar'])];
    }

    public function sidebar(): string
    {
        $mode = $this->requestStack->getCurrentRequest()?->cookies->get(self::COOKIE);

        return \in_array($mode, self::MODES, true) ? $mode : 'auto';
    }
}
