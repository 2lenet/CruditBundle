<?php

namespace Lle\CruditBundle\Exporter;

use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Exception\ExporterException;

class Exporter
{
    public const CSV = 'csv';
    public const EXCEL = 'xls';
    public const PDF = 'pdf';

    public function __construct(
        protected iterable $exporters,
    ) {
    }

    public function export(iterable $resources, string $format, ExportParams $params, array $totals = []): string
    {
        return $this->getExporter($format)->export($resources, $params, $totals);
    }

    public function getContentType(string $format): string
    {
        return $this->getExporter($format)->getContentType();
    }

    public function getFileExtension(string $format): string
    {
        return $this->getExporter($format)->getFileExtension();
    }

    protected function getExporter(string $format): ExporterInterface
    {
        /** @var ExporterInterface $exporter */
        foreach ($this->exporters as $exporter) {
            if ($format === $exporter->getSupportedFormat()) {
                return $exporter;
            }
        }

        throw new ExporterException("Export action: format '$format' is not supported");
    }
}
