<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Twig\Attribute\AsTwigFunction;

/**
 * Form themes of the forms shown by Crudit: the theme of Crudit, then the global themes of the project
 * (twig.form_themes) that are not a layout (Bootstrap, Foundation, Tailwind…), so that the widgets of the third-party
 * bundles (FOSCKEditor, Vich…) and the blocks of the project keep working. A layout would override the blocks of
 * CruditUiBundle: it is left out.
 */
class FormThemesExtension
{
    public const CRUDIT_THEME = '@LleCrudit/form/theme.html.twig';

    private const LAYOUT_PATTERN = '/_layout\.html\.twig$/';

    /**
     * @param list<string> $resources twig.form.resources
     */
    public function __construct(private readonly array $resources = [])
    {
    }

    /**
     * @return list<string>
     */
    #[AsTwigFunction('crudit_form_themes')]
    public function themes(): array
    {
        $themes = [];
        foreach ($this->resources as $resource) {
            if ($resource === self::CRUDIT_THEME || preg_match(self::LAYOUT_PATTERN, $resource) === 1) {
                continue;
            }
            $themes[] = $resource;
        }

        // The theme of Crudit last: its blocks win over those of the project, as before.
        $themes[] = self::CRUDIT_THEME;

        return $themes;
    }
}
