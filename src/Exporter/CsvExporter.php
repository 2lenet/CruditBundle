<?php

namespace Lle\CruditBundle\Exporter;

use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Exception\ExporterException;
use Symfony\Contracts\Translation\TranslatorInterface;

class CsvExporter extends AbstractExporter
{
    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

    public function getSupportedFormat(): string
    {
        return Exporter::CSV;
    }

    public function getContentType(): string
    {
        return 'text/csv';
    }

    public function export(iterable $resources, ExportParams $params, array $total = []): string
    {
        $path = tempnam(sys_get_temp_dir(), Exporter::CSV);

        if ($path === false) {
            throw new ExporterException("Unknown CSV exporter error");
        }

        $file = fopen($path, "w");
        $headersAdded = false;

        if ($file) {
            /** @var ResourceView $resource */
            foreach ($resources as $resource) {
                if ($params->getIncludeHeaders() && !$headersAdded) {
                    $headers = $this->getHeaders($resource->getFields());
                    fputcsv($file, $headers, $params->getSeparator());
                    $headersAdded = true;
                }

                $line = [];

                /** @var FieldView $field */
                foreach ($resource->getFields() as $field) {
                    $line[] = $this->getValue($field);
                }

                fputcsv($file, $line, $params->getSeparator());
            }
        }

        return $path;
    }

    protected function getHeaders(array $fields): array
    {
        $result = [];

        /** @var FieldView $field */
        foreach ($fields as $field) {
            $result[] = $this->translator->trans($field->getField()->getLabel());
        }

        return $result;
    }
}
