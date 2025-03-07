<?php

namespace Lle\CruditBundle\Exporter;

use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Exception\ExporterException;

class Exporter
{
    public const CSV = 'csv';
    public const EXCEL = 'xls';
    public const PDF = 'pdf';

    protected iterable $exporters;

    public function __construct(iterable $exporters)
    {
        $this->exporters = $exporters;
    }

    public function export(iterable $resources, string $format, ExportParams $params, array $totals = []): string
    {
        /** @var ExporterInterface $exporter */
        foreach ($this->exporters as $exporter) {
            if ($format === $exporter->getSupportedFormat()) {
                return $exporter->export($resources, $params, $totals);
            }
        }

        throw new ExporterException("Export action: format '$format' is not supported");
    }
}
