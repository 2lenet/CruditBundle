<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeField extends DateField
{
    public function support(string $type): bool
    {
        return (in_array($type, ['datetime', self::class]));
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            'format' => 'd/m/Y H:i'
        ])->setAllowedTypes('format', 'string');
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/datetime.html.twig';
    }
}
