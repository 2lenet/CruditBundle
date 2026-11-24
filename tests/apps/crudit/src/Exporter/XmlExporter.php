<?php

declare(strict_types=1);

namespace App\Exporter;

use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Exporter\ExportParams;

/**
 * An exporter of a project (the XML file of a software): not a format of the bundle, so a list offers it only when the
 * `export` config of one of its actions chooses it (the interventions).
 */
class XmlExporter implements ExporterInterface
{
    public function getSupportedFormat(): string
    {
        return 'xml';
    }

    public function getFileExtension(): string
    {
        return '.xml';
    }

    public function getContentType(): string
    {
        return 'application/xml';
    }

    public function export(iterable $resources, ExportParams $params, array $totals = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xml');
        file_put_contents($path, '<?xml version="1.0" encoding="UTF-8"?><export/>');

        return $path;
    }
}
