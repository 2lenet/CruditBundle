<?php

namespace phpunit\Unit\Exporter;

use Lle\CruditBundle\Exporter\PdfExporter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
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

    /**
     * Mpdf::save() splits the HTML on a body marker: everything before it goes to mPDF in a
     * single WriteHTML() call, the rest one line at a time. Since the callback rewrites the
     * <body> tag, it has to restore that marker, otherwise the <head> - and its <style> block -
     * is fed to mPDF line by line and printed as plain text instead of being applied.
     */
    public function testHeaderAndFooterCallbackKeepsBodySplitPoint(): void
    {
        $html = $this->generateHtml();
        $bodyLocation = strpos($html, PdfExporter::SIMULATED_BODY_START);

        self::assertNotFalse($bodyLocation, 'The body split point expected by Mpdf::save() is missing.');

        $firstChunk = substr($html, 0, $bodyLocation);
        self::assertStringContainsString('<style', $firstChunk);
        self::assertStringContainsString('<htmlpageheader name="myHeader1"', $firstChunk);
        self::assertStringContainsString('<htmlpagefooter name="myFooter2"', $firstChunk);
    }

    /**
     * The marker is duplicated in the exporter because PhpSpreadsheet only exposes it since 2.0.
     * Whenever the writer does expose it, both values must stay in sync.
     */
    public function testSimulatedBodyStartMatchesTheWriterMarker(): void
    {
        if (!defined(Mpdf::class . '::SIMULATED_BODY_START')) {
            self::markTestSkipped('PhpSpreadsheet < 2.0 does not expose the marker.');
        }

        self::assertSame(Mpdf::SIMULATED_BODY_START, PdfExporter::SIMULATED_BODY_START);
    }

    public function testHeaderAndFooterCallbackInjectsPageHeaderAndFooter(): void
    {
        $html = $this->generateHtml();

        self::assertStringContainsString('odd-header-name: html_myHeader1;', $html);
        self::assertStringContainsString('odd-footer-name: html_myFooter2;', $html);
        foreach (self::HEADER_FOOTER as $value) {
            self::assertStringContainsString($value, $html);
        }
    }

    /**
     * Header and footer values come from the crud config, so they can hold any character. They
     * are injected into the replacement string, where $1, \1 and \\ would be read as
     * backreferences by preg_replace() and silently swallow part of the value.
     *
     * @dataProvider provideSpecialCharacters
     */
    public function testHeaderAndFooterCallbackKeepsSpecialCharacters(string $value): void
    {
        $html = $this->generateHtml(['header-left' => $value]);

        self::assertStringContainsString($value, $html);
    }

    /** @return array<string, array{string}> */
    public function provideSpecialCharacters(): array
    {
        return [
            'backslash' => ['C:\\exports\\2026'],
            'backslash followed by a digit' => ['Filter \\1 enabled'],
            'dollar followed by a digit' => ['Amount $1 net'],
            'double backslash' => ['a\\\\b'],
            'ampersand' => ['Durand & Sons'],
        ];
    }

    private function generateHtml(array $headerFooter = []): string
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $exporter = new PdfExporter($translator);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Value');

        $writer = new Mpdf($spreadsheet);
        $writer->setEditHtmlCallback(
            $exporter->getHeaderAndFooter($headerFooter + self::HEADER_FOOTER)
        );

        return $writer->generateHtmlAll();
    }
}
