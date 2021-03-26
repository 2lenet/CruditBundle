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

    /** @param mixed $value */
    public function getStringValue($value, string $format): ?string
    {
        if ($value instanceof \DateTime) {
            return $value->format($format);
        }
        return null;
    }

    public function configureOptions(Field $field): array
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setDefaults([
            'format' => 'd-m-Y H:i'
        ])->setAllowedTypes('format', 'string');
        return $optionResolver->resolve($field->getOptions());
    }
}
