<?php


namespace Lle\CruditBundle\Exporter;


use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Exception\ExporterException;
use Symfony\Component\HttpFoundation\Response;

class Exporter
{
    public const CSV = "csv";
    public const EXCEL = "excel";

    protected iterable $exporters;

    public function __construct(iterable $exporters)
    {
        $this->exporters = $exporters;
    }

    public function export($resources, string $format): Response
    {
        /** @var ExporterInterface $exporter */
        foreach ($this->exporters as $exporter) {
            if ($format === $exporter->getSupportedFormat()) {
                return $exporter->export($resources, $format);
            }
        }

        throw new ExporterException("Export action: format '$format' is not supported");
    }
}
