<?php

namespace Lle\CruditBundle\Exporter;

use Doctrine\Common\Collections\Collection;
use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Field\CurrencyField;
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
                    $format = $field->getOptions()["format"];
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

        return trim((string)$result);
    }
}
