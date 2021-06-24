<?php


namespace Lle\CruditBundle\Exporter;


use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Exception\ExporterException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CsvExporter implements ExporterInterface
{
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

        /** @var ResourceView $resource */
        foreach ($resources as $resource) {

            $line = [];

            /** @var FieldView $field */
            foreach ($resource->getFields() as $field) {
                $line[] = (string)$field->getValue();
            }

            fputcsv($file, $line, $params->getSeparator() ?? ";");
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
}
