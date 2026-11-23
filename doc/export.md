# How to export data

Crudit adds an exporter that handles data export for multiple configurable formats.

By default, Crudit adds an "Export" button for all lists and will export index fields.

### Usage

In your CrudConfig::getListActions, you can add the export action manually.

This is already configured by default.

```php

public function getListActions(): array
{
    $actions[] = ListAction::new(
        "action.export",
        $this->getPath(CrudConfigInterface::EXPORT),
        Icon::new("file-export")
    )
        ->setModal("@LleCrudit/modal/_export.html.twig")
        ->setConfig(
            [
                // you can change this part to put whatever you want
                "export" => [Exporter::CSV, Exporter::EXCEL],
            ]
        );
}

public function getFields($key): array
{
    if ($key === CrudConfigInterface::EXPORT) {
        return [
            Field::new(...)
        ];
    }

    // if EXPORT is not filled, INDEX is used instead
}
```

Crudit already comes with some exporters:

- CsvExporter for csv files
- ExcelExporter for xls files
- PdfExporter for pdf files

### Configuration

If you want to change the behaviour of the exporters, you can edit your CrudConfig::getExportParams.

```php
public function getExportParams(string $format): ExportParams
{
    // $format contains Exporter::CSV, Exporter:EXCEL or whatever else you added
    return ExportParams::new()
        ->setFilename($this->getName())
        ->setSeparator(";");
}
```

### The dialog

The action opens the dialog `LleCruditUi:ExportDialog`: the format as cards, and a sentence that says the file holds
**all the results** of the list with the current filters and sort order. The formats are those of the `export` key of the
action (in this order); without that key, all the exporters are offered. The download is followed by the controller
`export` of CruditUiBundle (enable it in `assets/controllers.json`): the dialog closes and a toast says it is done.

The CSV starts with a UTF-8 byte order mark so that Excel reads the accents: `ExportParams::setBom(false)` leaves it out.
Details and migration: `UPGRADE-2.0.md`, "The export".

### Extending

You can add your own exporters.

Your exporter must implement `Lle\CruditBundle\Contracts\ExporterInterface` (autoconfigured, tag `crudit.exporter`):

* `getSupportedFormat`: your format (`custom`). Add it to the `export` config of your ListAction, or leave that key out to
  offer every exporter.
* `getFileExtension` (`.json`) and `getContentType` (`application/json`).
* `export`: writes the file and **returns its path**; the controller sends it and deletes it. Throw an
  `ExporterException` for a failure: the user goes back to the list with a message.

To name the format in the dialog, implement `DescribedExporterInterface` instead: `getLabel()`, `getDescription()` and
`getIcon()` (`file-excel`, `file-csv`, `file-pdf`, or `file`). Without it, the dialog uses the translation `export.<format>`
of the domain of the list, else the code in capitals.

Example:

```php
<?php

namespace App\Service\CustomExporter;

use Lle\CruditBundle\Contracts\DescribedExporterInterface;
use Lle\CruditBundle\Exporter\ExportParams;

class JsonExporter implements DescribedExporterInterface
{
    public function getSupportedFormat(): string
    {
        return 'json';
    }

    public function getFileExtension(): string
    {
        return '.json';
    }

    public function getContentType(): string
    {
        return 'application/json';
    }

    public function getLabel(): string
    {
        return 'JSON';
    }

    public function getDescription(): ?string
    {
        return 'For other programs';
    }

    public function getIcon(): ?string
    {
        return null;
    }

    public function export(iterable $resources, ExportParams $params, array $totals = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'json');
        $rows = [];
        foreach ($resources as $resource) {
            $row = [];
            foreach ($resource->getFields() as $field) {
                $row[$field->getField()->getName()] = $field->getRawValue();
            }
            $rows[] = $row;
        }
        file_put_contents($path, json_encode($rows));

        return $path;
    }
}
```

### Pdf exporter

The PDF exporter can be personalized in the crud config: 

```php

public function getExportParams(string $format): ExportParams
    {
        $exportParams = parent::getExportParams($format);
        $params = $exportParams->getPdfParams();
        $params['locale'] = 'fr'
        $params['header-footer'] = [
                'header-left' => 'I am the page header',
                'footer-center' => 'Made with Crudit',
        ];
        $exportParams->setPdfParams($params);

        return $exportParams;
    }
```

The default values are:

```php
[
    'title' => 'Export',
    'paper_size' => 9, //A4
    'orientation' => 'landscape',
    'decimal_separator' => ',',
    'thousands_separator' => '.',
    'locale' => 'fr',
    'header-footer' => [
        'header-left' => '',
        'header-center' => '',
        'header-right' => '',
        'footer-left' => '',
        'footer-center' => '',
        'footer-right' => '',
    ],
];
```

To customize the values, check the [PhpSpreadsheet documentation](https://phpspreadsheet.readthedocs.io/en/latest/),
