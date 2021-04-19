<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Dto\Field\Field;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeField extends DateField
{

    public function support(string $type): bool
    {
        return (in_array($type, ['datetime', self::class]));
    }

    public function configureOptions(Field $field): array
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setDefaults([
            'format' => 'd-m-Y H:i'
        ])->setAllowedTypes('format', 'string');
        return $optionResolver->resolve($field->getOptions());
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/datetime.html.twig';
    }
}
