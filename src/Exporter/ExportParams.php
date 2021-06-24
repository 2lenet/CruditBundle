<?php


namespace Lle\CruditBundle\Exporter;


class ExportParams
{
    protected $filename;

    protected string $separator;

    public static function new(): self
    {
        return new self();
    }

    /**
     * Supported by: CSV, Excel
     */
    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename($filename): self
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
}
