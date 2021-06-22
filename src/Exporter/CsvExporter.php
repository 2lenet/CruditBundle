<?php


namespace Lle\CruditBundle\Exporter;


use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Exception\ExporterException;
use Lle\CruditBundle\Resolver\ResourceResolver;
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
        $file = tmpfile();

        if ($file === false) {
            throw new ExporterException("Unknown CSV exporter error");
        }

        /** @var ResourceView $resource */
        foreach ($resources as $resource) {

            $line = [];

            /** @var FieldView $field */
            foreach ($resource->getFields() as $field) {
                $line[] = (string)$field->getValue();
            }

            fputcsv($file, $line, $params->getSeparator() ?? ";");
        }

        $response = new Response(file_get_contents(stream_get_meta_data($file)['uri']));

        fclose($file);

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
