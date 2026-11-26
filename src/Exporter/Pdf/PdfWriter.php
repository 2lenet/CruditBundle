<?php

namespace Lle\CruditBundle\Exporter\Pdf;

use Lle\CruditBundle\Exception\ExporterException;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class PdfWriter
{
    public const PAGE_NUMBER_PLACEHOLDER = '{PAGENO}';
    public const PAGE_COUNT_PLACEHOLDER = '{nbpg}';

    private const FONT = 'helvetica';
    private const BODY_FONT_SIZE = 8.0;
    private const MM_PER_INCH = 25.4;
    private const ZEBRA_COLOR = '#E8E8E8';

    private const PAPER_FORMATS = [
        PageSetup::PAPERSIZE_A3 => 'A3',
        PageSetup::PAPERSIZE_A4 => 'A4',
        PageSetup::PAPERSIZE_A5 => 'A5',
        PageSetup::PAPERSIZE_LETTER => 'LETTER',
        PageSetup::PAPERSIZE_LEGAL => 'LEGAL',
    ];

    public function __construct(private readonly array $params, private readonly bool $compress = true)
    {
    }

    /**
     * @param string[] $headers
     * @param string[][] $rows
     * @param string[]|null $totals
     * @param string[] $alignments left|center|right, by column
     */
    public function write(array $headers, array $rows, ?array $totals, array $alignments): string
    {
        $this->defineFontPath();

        $pdf = new BandedTcpdf($this->params['header-footer'] ?? [], $this->compress);
        $pdf->setTitle((string)($this->params['title'] ?? ''));

        $format = self::PAPER_FORMATS[$this->params['paper_size'] ?? PageSetup::PAPERSIZE_A4] ?? 'A4';
        $landscape = ($this->params['orientation'] ?? PageSetup::ORIENTATION_LANDSCAPE)
            === PageSetup::ORIENTATION_LANDSCAPE;
        $margins = [
            'top' => $this->toMillimeters('margin-top'),
            'right' => $this->toMillimeters('margin-right'),
            'bottom' => $this->toMillimeters('margin-bottom'),
            'left' => $this->toMillimeters('margin-left'),
        ];
        $page = $pdf->addPage([
            'format' => $format,
            'orientation' => $landscape ? 'L' : 'P',
            'margin' => [
                'PL' => $margins['left'],
                'PR' => $margins['right'],
                'PT' => $margins['top'],
                'PB' => $margins['bottom'],
                'CT' => $margins['top'],
                'CB' => $margins['bottom'],
            ],
        ]);

        $font = $pdf->font->insert($pdf->pon, self::FONT, '', self::BODY_FONT_SIZE);
        $pdf->page->addContent($font['out']);
        $pdf->addHTMLCell(
            html: $this->buildTable($headers, $rows, $totals, $alignments),
            posx: $margins['left'],
            posy: $margins['top'],
            width: $page['width'] - $margins['left'] - $margins['right'],
        );
        $pdf->addBands();

        return $pdf->getOutPDFString();
    }

    private function toMillimeters(string $key): float
    {
        return (float)($this->params[$key] ?? 0) * self::MM_PER_INCH;
    }

    private function defineFontPath(): void
    {
        if (defined('K_PATH_FONTS')) {
            return;
        }

        $path = \Composer\InstalledVersions::getInstallPath('tecnickcom/tc-lib-pdf-font') . '/target/fonts';
        if (!is_dir($path)) {
            throw new ExporterException(
                'PDF fonts are missing: run "php vendor/tecnickcom/tc-lib-pdf-font/util/build_fonts.php"'
                . ' (see doc/export.md)'
            );
        }

        define('K_PATH_FONTS', realpath($path));
    }

    private function buildTable(array $headers, array $rows, ?array $totals, array $alignments): string
    {
        $html = '<table border="0" cellpadding="3" cellspacing="0"><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th align="center" style="font-weight: bold; border: 0.5px solid black;">'
                . $this->escape($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $index => $row) {
            $html .= $this->buildRow($row, $alignments, $index % 2 === 0 ? self::ZEBRA_COLOR : null, false);
        }
        if ($totals !== null) {
            $html .= $this->buildRow($totals, $alignments, null, true);
        }

        return $html . '</tbody></table>';
    }

    private function buildRow(array $cells, array $alignments, ?string $background, bool $bold): string
    {
        $style = ($background ? 'background-color: ' . $background . ';' : '')
            . ($bold ? 'font-weight: bold; border: 0.5px solid black;' : '');
        $html = '<tr>';
        foreach ($cells as $column => $cell) {
            $html .= '<td align="' . ($alignments[$column] ?? 'left') . '" style="' . $style . '">'
                . $this->escape((string)$cell) . '</td>';
        }

        return $html . '</tr>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
