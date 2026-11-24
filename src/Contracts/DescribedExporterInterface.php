<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

/**
 * An exporter that describes itself in the dialog of the export (lot 3.1.g). Optional: without it, the dialog shows the
 * translation `export.<format>` of the domain of the list (1.x), else the code of the format in capitals.
 */
interface DescribedExporterInterface extends ExporterInterface
{
    /**
     * The name of the format, already translated ("Excel").
     */
    public function getLabel(): string;

    /**
     * A sentence that says what the file is for ("A spreadsheet to rework the data"), already translated; null for none.
     */
    public function getDescription(): ?string;

    /**
     * An icon of the catalogue of CruditUiBundle (`file-excel`, `file-csv`, `file-pdf`, `file`); null for `file`.
     */
    public function getIcon(): ?string;
}
