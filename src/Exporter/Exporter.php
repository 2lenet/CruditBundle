<?php

namespace Lle\CruditBundle\Exporter;

use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Exception\ExporterException;

class Exporter
{
    public const CSV = 'csv';
    public const EXCEL = 'xls';
    public const PDF = 'pdf';

    /** The formats of the bundle: what an action offers when its config does not choose. */
    public const BUNDLE_FORMATS = [self::CSV, self::EXCEL, self::PDF];

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
     * The formats of the bundle that have an exporter. A project exporter is often written for one entity (an XML file of
     * the invoices): it is offered only where an action chooses it.
     *
     * @return list<string>
     */
    public function getDefaultFormats(): array
    {
        return array_values(array_filter(self::BUNDLE_FORMATS, fn (string $format): bool => $this->supports($format)));
    }

    /**
     * The formats an action offers: those of `setConfig(['export' => [...]])`, in that order, without the codes that
     * no exporter knows (a typo, a removed exporter); the formats of the bundle when it has none (no key, an empty list,
     * only unknown codes).
     *
     * @param array<string, mixed> $config the config of the action
     *
     * @return list<string>
     */
    public function resolveFormats(array $config): array
    {
        $configured = $config['export'] ?? null;
        $formats = [];
        if (is_iterable($configured)) {
            foreach ($configured as $code) {
                if (is_string($code) && $this->supports($code)) {
                    $formats[] = $code;
                }
            }
        }

        return $formats !== [] ? array_values(array_unique($formats)) : $this->getDefaultFormats();
    }

    public function supports(string $format): bool
    {
        return $this->findExporter($format) !== null;
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
        return $this->findExporter($format)
            ?? throw new ExporterException("Export action: format '$format' is not supported");
    }
}
