<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegerField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ['integer', 'smallint', 'bigint', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/integer.html.twig';
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            "tableCssClass" => "text-end",
            'thousands_separator' => ' ',
        ]);
    }
}
