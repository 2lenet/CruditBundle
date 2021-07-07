<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Dto\Field\Field;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateField extends AbstractField
{

    public function support(string $type): bool
    {
        return (in_array($type, ['date', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/date.html.twig';
    }

    public function configureOptions(Field $field): array
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setDefaults([
            'format' => 'd/m/Y'
        ])->setAllowedTypes('format', 'string');
        return $optionResolver->resolve($field->getOptions());
    }
}
