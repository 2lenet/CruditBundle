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

The PDF is rendered with [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf) (the successor of TCPDF),
directly from the exported rows: PhpSpreadsheet is not involved. In the header and footer values,
`{PAGENO}` is replaced by the current page number and `{nbpg}` by the total number of pages. The values are
written as plain text, and the `size-*` keys accept the CSS keywords `xx-small` to `xx-large`.

`paper_size` (PhpSpreadsheet `PageSetup::PAPERSIZE_*` constants: A3, A4, A5, Letter and Legal are supported),
`orientation` and the `margin-*` keys (in inches) keep their meaning.

#### Fonts

The bundle ships the Helvetica family (about 100 KB, Adobe core font metrics, licence file included) and points
`K_PATH_FONTS` at it, so nothing has to be installed or generated.

Helvetica only covers the Western European characters (WinAnsi): accents and `€` are fine, but other scripts
(Polish `ŁŻ`, Cyrillic, Greek...) are replaced by `?`. If your data needs them, generate a Unicode font
(for instance DejaVu Sans, see the
[tc-lib-pdf fonts documentation](https://github.com/tecnickcom/tc-lib-pdf/blob/main/doc/FONTS.md)), define
`K_PATH_FONTS` to the directory holding it before the first export, and select it with the `font` key of the
PDF params (`$params['font'] = 'dejavusans';`).

#### Layout and memory

The columns are sized from their content: each one is as wide as its longest value (capped at 40 characters, so a
long text or URL cannot take the whole page) and never narrower than the longest word of its header. When the
table is still too wide for the page, the font shrinks (down to 5 pt) to make it fit, as the former "fit to width"
did. The header and footer are centered in the top and bottom margins.

The rows and the HTML table are held in memory while the document is built: expect about 20 MB per 1 000 rows.
With a `memory_limit` of 128M, an export is limited to roughly 6 000 rows.

Compared with the former output, the vertical borders of the data cells are kept, and the zebra striping starts on
the second data row.

### Upgrade note: PdfExporter rewritten on tc-lib-pdf

`mpdf/mpdf` (GPL) and `dompdf/dompdf` are no longer dependencies of the bundle; PDFs are rendered with
`tecnickcom/tc-lib-pdf` (LGPL). An application that uses mPDF directly must require it itself.

`PdfExporter` no longer goes through PhpSpreadsheet. Removed: `getHeaderAndFooter()`, `pageSetup()`,
`getNumberDecimalFormat()`, and the protected `getType()` and `formatParticularFieldType()`; the
`setEditHtmlCallback()` injection is gone too. A class extending `PdfExporter` that calls or overrides them must be
adapted: the cell formatting is now done by `formatField()`, `formatBoolean()` and `formatCurrency()`, which return
strings, and `createWriter()` builds the writer.

Header and footer values are passed to `Lle\CruditBundle\Exporter\Pdf\PdfWriter` and written as plain text, so
HTML in those values is no longer interpreted. The font is Helvetica instead of Arial, so exported PDFs look
slightly different.
