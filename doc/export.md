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
