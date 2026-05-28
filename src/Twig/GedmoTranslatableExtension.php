<?php

namespace Lle\CruditBundle\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GedmoTranslatableExtension extends AbstractExtension
{
    public function __construct(
        private string $defaultLocale,
        private TranslatorInterface $translator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('crudit_gedmo_default_locale_helper', [$this, 'getDefaultLocaleHelper']),
        ];
    }

    public function getDefaultLocaleHelper(): string
    {
        $localeName = ucfirst(\Locale::getDisplayLanguage($this->defaultLocale, $this->defaultLocale));
        $prefix = $this->translator->trans(
            'crudit.gedmo_translatable.default_locale_helper',
            [],
            'LleCruditBundle'
        );

        return $prefix . ' ' . $localeName;
    }
}
