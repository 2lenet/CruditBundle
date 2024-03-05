<?php

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Exporter\ExportParams;
use Symfony\Component\HttpFoundation\Response;

interface ExporterInterface
{
    /**
     * @return string the supported format (e.g. csv)
     */
    public function getSupportedFormat(): string;

    /**
     * @param iterable $resources an iterable of ResourceView
     * @param ExportParams $params the parameters of the export
     * @return Response the file
     */
    public function export(iterable $resources, ExportParams $params, array $totals = []): Response;
}
