<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DateField extends AbstractField
{
    /** Format of the exports when the field has none: the screens use the format of the locale. */
    public const string EXPORT_FORMAT = 'd/m/Y';

    public function support(string $type): bool
    {
        return (in_array($type, ['date', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/date.html.twig';
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        // null: short format of the locale of the request (13/10/2026 in fr, 10/13/26 in en)
        $optionsResolver->setDefaults([
            'format' => null,
        ])->setAllowedTypes('format', ['null', 'string']);
    }
}
