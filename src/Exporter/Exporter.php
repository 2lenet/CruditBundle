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

    /**
     * The codes of the formats of all the exporters (those of the bundle and those of the projects), in the order of
     * the services.
     *
     * @return list<string>
     */
    public function getFormats(): array
    {
        $formats = [];
        /** @var ExporterInterface $exporter */
        foreach ($this->exporters as $exporter) {
            $formats[] = $exporter->getSupportedFormat();
        }

        return array_values(array_unique($formats));
    }

    /**
     * The exporter of a format, to describe it in the dialog of the export (DescribedExporterInterface).
     */
    public function findExporter(string $format): ?ExporterInterface
    {
        /** @var ExporterInterface $exporter */
        foreach ($this->exporters as $exporter) {
            if ($format === $exporter->getSupportedFormat()) {
                return $exporter;
            }
        }

        return null;
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
