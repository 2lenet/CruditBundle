<?php


namespace Lle\CruditBundle\Contracts;


use Symfony\Component\HttpFoundation\Response;

interface ExporterInterface
{
    public function getSupportedFormat(): string;

    public function export($resources, $format): Response;
}
