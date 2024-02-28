<?php

namespace Lle\CruditBundle\Exporter;

use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Dto\FieldView;

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
                default:
                    $result = $field->getRawValue();
            }
        }

        return trim((string)$result);
    }
}
