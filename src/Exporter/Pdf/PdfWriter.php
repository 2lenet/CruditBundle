<?php

namespace Lle\CruditBundle\Exporter\Pdf;

use Lle\CruditBundle\Exception\ExporterException;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class PdfWriter
{
    public const PAGE_NUMBER_PLACEHOLDER = '{PAGENO}';
    public const PAGE_COUNT_PLACEHOLDER = '{nbpg}';

    private const DEFAULT_FONT = 'helvetica';
    private const BODY_FONT_SIZE = 8.0;
    private const MIN_FONT_SIZE = 5.0;
    private const MM_PER_INCH = 25.4;
    private const POINTS_PER_INCH = 72.0;
    private const ZEBRA_COLOR = '#E8E8E8';
    private const BORDER = '0.5px solid black';

    // Average Helvetica glyph width relative to the font size, and the cell padding in characters.
    private const CHAR_WIDTH_RATIO = 0.5;
    private const CELL_PADDING_CHARS = 2;
    private const MAX_COLUMN_CHARS = 40;

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
        $font = (string)($this->params['font'] ?? self::DEFAULT_FONT);
        $this->defineFontPath();
        $this->assertFontAvailable($font);
        $pdf = new BandedTcpdf($this->params['header-footer'] ?? [], $font, $this->compress);
        $pdf->setTitle((string)($this->params['title'] ?? ''));

        $margins = [
            'top' => $this->toMillimeters('margin-top'),
            'right' => $this->toMillimeters('margin-right'),
            'bottom' => $this->toMillimeters('margin-bottom'),
            'left' => $this->toMillimeters('margin-left'),
        ];
        $page = $pdf->addPage([
            'format' => self::PAPER_FORMATS[$this->params['paper_size'] ?? PageSetup::PAPERSIZE_A4] ?? 'A4',
            'orientation' => $this->isLandscape() ? 'L' : 'P',
            'margin' => [
                'PL' => $margins['left'],
                'PR' => $margins['right'],
                'PT' => $margins['top'],
                'PB' => $margins['bottom'],
                'CT' => $margins['top'],
                'CB' => $margins['bottom'],
            ],
        ]);

        $width = $page['width'] - $margins['left'] - $margins['right'];
        $layout = $this->getLayout($headers, $rows, $totals, $width);
        $bodyFont = $pdf->font->insert($pdf->pon, $font, '', $layout['fontSize']);
        $pdf->page->addContent($bodyFont['out']);
        $pdf->addHTMLCell(
            html: $this->buildTable($headers, $rows, $totals, $alignments, $layout['widths']),
            posx: $margins['left'],
            posy: $margins['top'],
            width: $width,
        );
        $pdf->addBands();

        return $pdf->getOutPDFString();
    }

    /**
     * Column widths (percent) and font size that make the table fit the page width: a column is
     * as wide as its content (capped, so that one long text cannot take the whole page) and never
     * narrower than the longest word of its header; the font shrinks when it still does not fit.
     *
     * @param string[] $headers
     * @param string[][] $rows
     * @param string[]|null $totals
     * @return array{widths: float[], fontSize: float}
     */
    public function getLayout(array $headers, array $rows, ?array $totals, float $widthMm): array
    {
        $columnCount = max(count($headers), $rows ? count($rows[0]) : 0);
        $weights = [];
        for ($column = 0; $column < $columnCount; $column++) {
            $content = mb_strlen((string)($totals[$column] ?? ''));
            foreach ($rows as $row) {
                $content = max($content, mb_strlen((string)($row[$column] ?? '')));
            }
            $weights[] = max(
                $this->getLongestWord((string)($headers[$column] ?? '')),
                min($content, self::MAX_COLUMN_CHARS)
            ) + self::CELL_PADDING_CHARS;
        }

        $total = array_sum($weights) ?: 1;
        $availablePoints = $widthMm / self::MM_PER_INCH * self::POINTS_PER_INCH;
        $neededPoints = $total * self::CHAR_WIDTH_RATIO * self::BODY_FONT_SIZE;
        $fontSize = $neededPoints > $availablePoints
            ? max(self::MIN_FONT_SIZE, self::BODY_FONT_SIZE * $availablePoints / $neededPoints)
            : self::BODY_FONT_SIZE;

        return [
            'widths' => array_map(static fn(int $weight): float => round($weight / $total * 100, 2), $weights),
            'fontSize' => $fontSize,
        ];
    }

    private function getLongestWord(string $text): int
    {
        return max(array_map('mb_strlen', preg_split('/\s+/u', $text) ?: ['']));
    }

    private function isLandscape(): bool
    {
        return ($this->params['orientation'] ?? PageSetup::ORIENTATION_LANDSCAPE)
            === PageSetup::ORIENTATION_LANDSCAPE;
    }

    private function toMillimeters(string $key): float
    {
        return (float)($this->params[$key] ?? 0) * self::MM_PER_INCH;
    }

    // The bundle ships the Helvetica family, which is all the default font needs. Another font
    // needs its generated files: define K_PATH_FONTS before the first export (see doc/export.md).
    private function defineFontPath(): void
    {
        if (!defined('K_PATH_FONTS')) {
            define('K_PATH_FONTS', dirname(__DIR__, 2) . '/Resources/fonts');
        }
    }

    // K_PATH_FONTS is a process-wide constant that the classic TCPDF library also defines, with
    // its own font format: whichever loads first wins, so fail with a clear message here.
    private function assertFontAvailable(string $font): void
    {
        $directory = (string)constant('K_PATH_FONTS');
        $file = strtolower($font) . '.json';
        $candidates = [$directory . '/' . $file, ...(glob($directory . '/*/' . $file) ?: [])];
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return;
            }
        }

        throw new ExporterException(sprintf(
            'PDF font "%s" not found: K_PATH_FONTS is already defined as "%s" (probably by tecnickcom/tcpdf) and contains no %s.',
            $font,
            $directory,
            $file
        ));
    }

    /**
     * @param float[] $widths
     */
    private function buildTable(array $headers, array $rows, ?array $totals, array $alignments, array $widths): string
    {
        $html = '<table width="100%" border="0" cellpadding="3" cellspacing="0"><thead><tr>';
        foreach ($headers as $column => $header) {
            $html .= '<th width="' . $widths[$column] . '%" align="center" style="font-weight: bold; border: '
                . self::BORDER . ';">' . $this->escape($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $index => $row) {
            $html .= $this->buildRow($row, $alignments, $widths, $index % 2 === 1 ? self::ZEBRA_COLOR : null, false);
        }
        if ($totals !== null) {
            $html .= $this->buildRow($totals, $alignments, $widths, null, true);
        }

        return $html . '</tbody></table>';
    }

    private function buildRow(array $cells, array $alignments, array $widths, ?string $background, bool $bold): string
    {
        $style = 'border-left: ' . self::BORDER . '; border-right: ' . self::BORDER . ';'
            . ($background ? ' background-color: ' . $background . ';' : '')
            . ($bold ? ' font-weight: bold; border-top: ' . self::BORDER . '; border-bottom: ' . self::BORDER . ';' : '');
        $html = '<tr>';
        foreach ($cells as $column => $cell) {
            $html .= '<td width="' . ($widths[$column] ?? 0) . '%" align="' . ($alignments[$column] ?? 'left')
                . '" style="' . $style . '">' . $this->escape((string)$cell) . '</td>';
        }

        return $html . '</tr>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
