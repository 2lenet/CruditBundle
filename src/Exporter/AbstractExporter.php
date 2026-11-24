<?php

namespace Lle\CruditBundle\Exporter;

use Doctrine\Common\Collections\Collection;
use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Field\CurrencyField;
use Lle\CruditBundle\Field\DateField;
use Lle\CruditBundle\Field\DateTimeField;
use Lle\CruditBundle\Field\DoctrineEntityField;

abstract class AbstractExporter implements ExporterInterface
{
    protected function getValue(FieldView $field): string
    {
        if ($field->getField()->getTemplate()) {
            $result = $field->getValue();
        } else {
            switch ($field->getField()->getType()) {
                case "date":
                case "datetime":
                    $format = $this->getDateFormat($field);
                    if ($field->getRawValue()) {
                        $result = $field->getRawValue()->format($format);
                    } else {
                        $result = "";
                    }
                    break;
                case "decimal":
                    $result = $field->getValue();
                    break;
                case DoctrineEntityField::class:
                    if ($field->getRawValue() instanceof Collection) {
                        $result = $field->getRawValue()->count();
                    } else {
                        $result = (string)$field->getRawValue();
                    }
                    break;
                case CurrencyField::class:
                    $result = $field->getRawValue();
                    if ($field->getOptions()['isInt'] ?? false) {
                        $result /= 100;
                    }
                    break;
                default:
                    $result = $field->getRawValue();
            }
        }

        return trim($this->toString($result));
    }

    /**
     * The text of a value: a list (an array, a Doctrine collection) is its values joined by a comma, an enumeration its
     * value, a date the format of the exports (`d/m/Y H:i`, or `d/m/Y` when the time is exactly 00:00:00: a datetime at
     * midnight comes out without its time), an object that has no text is empty.
     */
    private function toString(mixed $value): string
    {
        if (is_iterable($value)) {
            return implode(', ', array_map(fn ($item): string => $this->toString($item), [...$value]));
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format($value->format('H:i:s') === '00:00:00' ? DateField::EXPORT_FORMAT : DateTimeField::EXPORT_FORMAT);
        }
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }
        if ($value instanceof \UnitEnum) {
            return $value->name;
        }
        if (is_object($value) && !$value instanceof \Stringable) {
            return '';
        }

        return (string) $value;
    }

    /**
     * Format of a date: the one of the field, or the format of the exports (the screens use the format of the locale).
     */
    protected function getDateFormat(FieldView $field): string
    {
        $type = $field->getField()->getType();
        $default = \in_array($type, ['datetime', DateTimeField::class], true) ? DateTimeField::EXPORT_FORMAT : DateField::EXPORT_FORMAT;

        return $field->getOptions()['format'] ?? $default;
    }
}
