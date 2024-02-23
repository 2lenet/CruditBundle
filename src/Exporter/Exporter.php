<?php

namespace Lle\CruditBundle\Exporter;

use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Exception\ExporterException;
use Symfony\Component\HttpFoundation\Response;

class Exporter
{
    public const CSV = "csv";
    public const EXCEL = "excel";
    public const PDF = "pdf";
    protected iterable $exporters;

    public function __construct(iterable $exporters)
    {
        $this->exporters = $exporters;
    }

    public function export(iterable $resources, string $format, ExportParams $params): Response
    {
        /** @var ExporterInterface $exporter */
        foreach ($this->exporters as $exporter) {
            if ($format === $exporter->getSupportedFormat()) {
                return $exporter->export($resources, $params);
            }
        }

        throw new ExporterException("Export action: format '$format' is not supported");
    }
}
