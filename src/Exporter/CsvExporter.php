<?php

namespace Lle\CruditBundle\Exporter;

use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Exception\ExporterException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

class CsvExporter extends AbstractExporter
{
    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getSupportedFormat(): string
    {
        return Exporter::CSV;
    }

    public function export(iterable $resources, ExportParams $params): Response
    {
        $path = tempnam(sys_get_temp_dir(), Exporter::CSV);

        if ($path === false) {
            throw new ExporterException("Unknown CSV exporter error");
        }

        $file = fopen($path, "w");
        $headersAdded = false;

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

        $response = new BinaryFileResponse($path);
        $response->deleteFileAfterSend();

        $filename = $params->getFilename() ?? "export";
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            "$filename.csv"
        );
        $response->headers->set("Content-Disposition", $disposition);
        $response->headers->set("Content-Type", "text/csv");

        return $response;
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
