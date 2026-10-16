<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeField extends DateField
{
    public const string EXPORT_FORMAT = 'd/m/Y H:i';

    public function support(string $type): bool
    {
        return (in_array($type, ['datetime', self::class]));
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        // null: short format of the locale of the request (13/10/2026 09:05 in fr)
        $optionsResolver->setDefaults([
            'format' => null,
        ])->setAllowedTypes('format', ['null', 'string']);
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/datetime.html.twig';
    }
}
