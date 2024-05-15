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

### Extending

You can add your own exporters.

Your exporter must implement Lle\CruditBundle\Contracts\ExporterInterface

* `getSupportedFormat`: your format. It has to be then added in the "export" config of your ListAction (see above)
* `export`: the actual export action. It must return a Response.

Example:

```php
<?php


namespace App\Service\CustomExporter;


class CustomExporter implements ExporterInterface
{
    public function getSupportedFormat(): string
    {
        return "custom";
    }

    public function export(iterable $resources, ExportParams $params): Response
    {
        return new Response("Hello from custom export!");
    }
}
```

Don't forget to add action in your CrudConfig

```php
ListAction::new()
    ->setModal("@LleCrudit/modal/_export.html.twig")
    ->setConfig(
        [
            "export" => ["custom"],
        ]
    );
```

Crudit will automatically detect the exporters and complete the export form.

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
