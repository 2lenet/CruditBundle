<?php

declare(strict_types=1);

namespace App\Exporter;

use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Exporter\ExportParams;

/**
 * An exporter of a project (the text file reserved to administrators): not a format of the bundle, so a list offers it only when the
 * `export` config of one of its actions chooses it (the interventions).
 */
class TxtExporter implements ExporterInterface
{
    public function getSupportedFormat(): string
    {
        return 'txt';
    }

    public function getFileExtension(): string
    {
        return '.txt';
    }

    public function getContentType(): string
    {
        return 'text/plain';
    }

    public function export(iterable $resources, ExportParams $params, array $totals = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($path, 'export');

        return $path;
    }
}
