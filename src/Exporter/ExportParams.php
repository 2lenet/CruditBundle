<?php

namespace Lle\CruditBundle\Exporter;

class ExportParams
{
    protected ?string $filename = null;

    protected string $separator = ";";

    protected bool $includeHeaders = true;

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
}
