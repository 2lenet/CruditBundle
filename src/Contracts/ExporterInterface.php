<?php


namespace Lle\CruditBundle\Contracts;


use Lle\CruditBundle\Exporter\ExportParams;
use Symfony\Component\HttpFoundation\Response;

interface ExporterInterface
{
    public function getSupportedFormat(): string;

    public function export(iterable $resources, string $format, ExportParams $params): Response;
}
