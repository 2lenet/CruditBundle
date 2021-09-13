<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberField extends AbstractField
{
    
    public function support(string $type): bool
    {
        return (in_array($type, ['integer', 'float', 'decimal', 'smallint','bigint', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/number.html.twig';
    }
    
    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            "tableCssClass"=>"text-end",
            'decimals' => '2',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
        ]);
    }
    
}
