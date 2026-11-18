<?php

declare(strict_types=1);

namespace phpunit\Unit\Twig;

use Lle\CruditBundle\Twig\FormThemesExtension;
use PHPUnit\Framework\TestCase;

class FormThemesExtensionTest extends TestCase
{
    public function testTheLayoutsOfSymfonyAreLeftOut(): void
    {
        $themes = (new FormThemesExtension([
            'form_div_layout.html.twig',
            'bootstrap_5_layout.html.twig',
            'bootstrap_4_horizontal_layout.html.twig',
            'foundation_6_layout.html.twig',
            'tailwind_2_layout.html.twig',
            '@FOSCKEditor/Form/ckeditor_widget.html.twig',
            '@VichUploader/Form/fields.html.twig',
        ]))->themes();

        self::assertSame([
            '@FOSCKEditor/Form/ckeditor_widget.html.twig',
            '@VichUploader/Form/fields.html.twig',
            FormThemesExtension::CRUDIT_THEME,
        ], $themes);
    }

    public function testAThemeOfTheProjectIsKeptWhateverItsName(): void
    {
        $themes = (new FormThemesExtension(['app_layout.html.twig', '@App/form/bootstrap_5_layout.html.twig']))->themes();

        self::assertSame(['app_layout.html.twig', '@App/form/bootstrap_5_layout.html.twig', FormThemesExtension::CRUDIT_THEME], $themes);
    }

    public function testTheThemeOfCruditIsLastAndOnce(): void
    {
        $themes = (new FormThemesExtension([FormThemesExtension::CRUDIT_THEME, '@App/form/theme.html.twig', '@App/form/theme.html.twig']))->themes();

        self::assertSame(['@App/form/theme.html.twig', FormThemesExtension::CRUDIT_THEME], $themes);
    }

    public function testWithoutAnyThemeOnlyCruditRemains(): void
    {
        self::assertSame([FormThemesExtension::CRUDIT_THEME], (new FormThemesExtension())->themes());
    }
}
