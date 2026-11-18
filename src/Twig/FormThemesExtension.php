<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Twig;

use Twig\Attribute\AsTwigFunction;

/**
 * Form themes of the forms shown by Crudit: the theme of Crudit, then the global themes of the project
 * (twig.form_themes) that are not a layout of Symfony (form_div, form_table, Bootstrap, Foundation, Tailwind), so that the widgets of the third-party
 * bundles (FOSCKEditor, Vich…) and the blocks of the project keep working. A layout would override the blocks of
 * CruditUiBundle: it is left out.
 */
class FormThemesExtension
{
    public const CRUDIT_THEME = '@LleCrudit/form/theme.html.twig';

    /** The layouts of Symfony, by their name (not a theme of the project, whatever its name) */
    private const LAYOUTS = [
        'form_div_layout.html.twig',
        'form_table_layout.html.twig',
        'bootstrap_3_layout.html.twig',
        'bootstrap_3_horizontal_layout.html.twig',
        'bootstrap_4_layout.html.twig',
        'bootstrap_4_horizontal_layout.html.twig',
        'bootstrap_5_layout.html.twig',
        'bootstrap_5_horizontal_layout.html.twig',
        'foundation_5_layout.html.twig',
        'foundation_6_layout.html.twig',
        'tailwind_2_layout.html.twig',
    ];

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
            if ($resource === self::CRUDIT_THEME || \in_array($resource, self::LAYOUTS, true)) {
                continue;
            }
            $themes[] = $resource;
        }

        // The theme of Crudit last: its blocks win over those of the project, as before.
        $themes[] = self::CRUDIT_THEME;

        return array_values(array_unique($themes));
    }
}
