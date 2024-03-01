<?php

namespace Lle\CruditBundle\Exporter;

use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ExportParams
{
    protected ?string $filename = null;
    protected string $separator = ";";
    protected bool $includeHeaders = true;
    protected array $pdfParams = [
        'title' => 'Export',
        'paper_size' => PageSetup::PAPERSIZE_A4,
        'orientation' => PageSetup::ORIENTATION_LANDSCAPE,
        'decimal_separator' => ',',
        'thousands_separator' => '.',
        'locale' => 'fr',
        'header-footer' => [],
    ];

    public static function new(): self
    {
        return new self();
    }

    /**
     * Supported by: CSV, Excel
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Supported by: CSV
     */
    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function setSeparator(string $separator): self
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Supported by: CSV, Excel
     */
    public function getIncludeHeaders(): bool
    {
        return $this->includeHeaders;
    }

    public function setIncludeHeaders(bool $includeHeaders): self
    {
        $this->includeHeaders = $includeHeaders;

        return $this;
    }

    public function getPdfParams(): array
    {
        return $this->pdfParams;
    }

    public function setPdfParams(array $pdfParams): self
    {
        $this->pdfParams = $pdfParams;

        return $this;
    }
}
