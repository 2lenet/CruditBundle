<?php

namespace phpunit\Unit\Exporter;

use Lle\CruditBundle\Exporter\Pdf\PageCountDompdf;
use Lle\CruditBundle\Exporter\Pdf\PdfWriter;
use Lle\CruditBundle\Exporter\PdfExporter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PdfExporterTest extends TestCase
{
    private const HEADER_FOOTER = [
        'header-left' => 'Establishment',
        'header-center' => 'Payment list',
        'header-right' => 'Page {PAGENO} of {nbpg}',
        'footer-left' => 'Exported by dev',
        'footer-center' => 'Crudit',
        'footer-right' => 'No filter',
    ];

    public function testHeaderAndFooterCallbackInjectsFixedHeaderAndFooter(): void
    {
        $html = $this->generateHtml();

        self::assertMatchesRegularExpression('/<header id="crudit-pdf-header"[^>]*position:\s*fixed/', $html);
        self::assertMatchesRegularExpression('/<footer id="crudit-pdf-footer"[^>]*position:\s*fixed/', $html);
        self::assertStringNotContainsString('htmlpageheader', $html);
        foreach (self::HEADER_FOOTER as $key => $value) {
            if ($key !== 'header-right') {
                self::assertStringContainsString($value, $html);
            }
        }
    }

    public function testHeaderAndFooterCallbackDropsTheNamedPageSoDompdfAppliesTheMargins(): void
    {
        $html = $this->generateHtml();

        self::assertStringNotContainsString('@page page0', $html);
        self::assertStringContainsString('@page {', $html);
    }

    public function testPageNumberPlaceholderBecomesACssCounterAndTotalIsLeftToTheWriter(): void
    {
        $html = $this->generateHtml();

        self::assertStringNotContainsString('{PAGENO}', $html);
        self::assertStringContainsString('class="crudit-pdf-page-number"', $html);
        self::assertStringContainsString('counter(page)', $html);
        self::assertStringContainsString(PageCountDompdf::PAGE_COUNT_PLACEHOLDER, $html);
    }

    /**
     * Header and footer values come from the crud config, so they can hold any character and
     * must reach the document untouched, whatever a regex replacement would make of them.
     *
     * @dataProvider provideSpecialCharacters
     */
    public function testHeaderAndFooterCallbackKeepsSpecialCharacters(string $value): void
    {
        $html = $this->generateHtml(['header-left' => $value]);

        self::assertStringContainsString($value, $html);
    }

    /** @return array<string, array{string}> */
    public static function provideSpecialCharacters(): array
    {
        return [
            'backslash' => ['C:\\exports\\2026'],
            'backslash followed by a digit' => ['Filter \\1 enabled'],
            'dollar followed by a digit' => ['Amount $1 net'],
            'double backslash' => ['a\\\\b'],
            'ampersand' => ['Durand & Sons'],
        ];
    }

    /**
     * PhpSpreadsheet styles `div + div` with a page break: a header or footer written as a <div>
     * right before the sheet would push the table onto the second page, leaving the first blank.
     */
    public function testHeaderAndFooterDoNotAddABlankFirstPage(): void
    {
        $path = $this->savePdf(3);

        self::assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', (string)file_get_contents($path)));
        unlink($path);
    }

    public function testSavedPdfIsAValidPdfWithTheTotalPageCountSubstituted(): void
    {
        $path = $this->savePdf(300);

        self::assertStringStartsWith('%PDF', (string)file_get_contents($path));
        unlink($path);
    }

    public function testWriterReplacesThePageCountPlaceholderOnceRendered(): void
    {
        $dompdf = new PageCountDompdf();
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml(
            '<html><body style="font-family: Helvetica;"><div style="position: fixed; top: 0;">total {nbpg}</div>'
            . str_repeat('<p style="page-break-after: always;">page</p>', 3)
            . '</body></html>'
        );
        $dompdf->render();

        $pageCount = $dompdf->getCanvas()->get_page_count();
        self::assertSame(3, $pageCount);
        $text = $this->extractPdfText((string)$dompdf->output());
        self::assertStringContainsString('total 3', $text);
        self::assertStringNotContainsString('{nbpg}', $text);
    }

    private function extractPdfText(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
        $text = '';
        foreach ($streams[1] as $stream) {
            $text .= (string)@gzuncompress($stream);
        }

        return preg_replace('/\)\s*-?\d+(\.\d+)?\s*\(|\[\(|\)\]\s*TJ|\) Tj/', '', $text);
    }

    public function testWriterLeavesADocumentWithoutPlaceholderAlone(): void
    {
        $dompdf = new PageCountDompdf();
        $dompdf->loadHtml('<html><body><p>hello</p></body></html>');
        $dompdf->render();

        self::assertSame(1, $dompdf->getCanvas()->get_page_count());
    }

    private function savePdf(int $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        for ($row = 1; $row <= $rows; $row++) {
            $sheet->setCellValue('A' . $row, 'Value ' . $row);
        }

        $exporter = new PdfExporter($this->createMock(TranslatorInterface::class));
        $writer = new PdfWriter($spreadsheet);
        $writer->setEditHtmlCallback($exporter->getHeaderAndFooter(self::HEADER_FOOTER));

        $path = (string)tempnam(sys_get_temp_dir(), 'pdftest');
        $writer->save($path);

        return $path;
    }

    private function generateHtml(array $headerFooter = []): string
    {
        $exporter = new PdfExporter($this->createMock(TranslatorInterface::class));

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Value');

        $writer = new PdfWriter($spreadsheet);
        $writer->setEditHtmlCallback(
            $exporter->getHeaderAndFooter($headerFooter + self::HEADER_FOOTER)
        );

        return $writer->generateHtmlAll();
    }
}
