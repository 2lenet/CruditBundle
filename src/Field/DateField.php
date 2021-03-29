<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateField implements FieldInterface
{

    public function support(string $type): bool
    {
        return (in_array($type, ['date', self::class]));
    }

    /** @param mixed $value */
    public function buildView(Field $field, $value): FieldView
    {
        $options = $this->configureOptions($field);
        return new FieldView(
            $field,
            $value,
            $this->getStringValue($value, $options['format'])
        );
    }

    /** @param mixed $value */
    public function getStringValue($value, string $format): ?string
    {
        if ($value instanceof \DateTime) {
            return  $value->format($format);
        }
        return null;
    }

    public function configureOptions(Field $field): array
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setDefaults([
            'format' => 'd-m-Y'
        ])->setAllowedTypes('format', 'string');
        return $optionResolver->resolve($field->getOptions());
    }
}
