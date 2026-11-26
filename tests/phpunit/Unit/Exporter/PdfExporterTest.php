<?php

namespace phpunit\Unit\Exporter;

use Lle\CruditBundle\Exception\ExporterException;
use Lle\CruditBundle\Exporter\Pdf\BandedTcpdf;
use Lle\CruditBundle\Exporter\Pdf\PdfWriter;
use PHPUnit\Framework\TestCase;

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

    public function testHeaderAndFooterAreWrittenOnEveryPage(): void
    {
        $pdf = $this->generatePdf(150);

        $pageCount = $this->countPages($pdf);
        self::assertGreaterThan(1, $pageCount);
        foreach (['Establishment', 'Payment list', 'Exported by dev', 'Crudit', 'No filter'] as $value) {
            self::assertSame($pageCount, substr_count($pdf, '(' . $value . ')'), $value);
        }
    }

    public function testPageNumberAndTotalAreSubstituted(): void
    {
        $pdf = $this->generatePdf(150);

        $pageCount = $this->countPages($pdf);
        self::assertStringContainsString('(Page 1 of ' . $pageCount . ')', $pdf);
        self::assertStringContainsString('(Page ' . $pageCount . ' of ' . $pageCount . ')', $pdf);
        self::assertStringNotContainsString('{nbpg}', $pdf);
        self::assertStringNotContainsString('{PAGENO}', $pdf);
    }

    /**
     * Header and footer values come from the crud config, so they can hold any character and
     * must reach the document untouched.
     *
     * @dataProvider provideSpecialCharacters
     */
    public function testHeaderAndFooterValuesAreWrittenVerbatim(string $value): void
    {
        $pdf = $this->generatePdf(3, ['header-left' => $value]);

        self::assertStringContainsString('(' . addcslashes($value, '\\()') . ')', $pdf);
    }

    /** @return array<string, array{string}> */
    public static function provideSpecialCharacters(): array
    {
        return [
            'backslash' => ['C:\\exports\\2026'],
            'dollar and digit' => ['Total $1 ($2)'],
            'html' => ['<b>R&D</b>'],
            'percent' => ['100% done'],
        ];
    }

    public function testMissingFontRaisesExplicitException(): void
    {
        $this->expectException(ExporterException::class);
        $this->expectExceptionMessage('K_PATH_FONTS');

        (new PdfWriter(['font' => 'nosuchfont']))->write(['A'], [['1']], null, ['left']);
    }

    public function testNoBlankFirstPage(): void
    {
        self::assertSame(1, $this->countPages($this->generatePdf(3)));
    }

    public function testSavedPdfIsAValidPdf(): void
    {
        self::assertStringStartsWith('%PDF', $this->generatePdf(3));
    }

    public function testWithoutHeaderAndFooterNothingIsWritten(): void
    {
        $pdf = $this->generatePdf(3, [], false);

        self::assertStringNotContainsString('(Establishment)', $pdf);
        self::assertStringNotContainsString('(Crudit)', $pdf);
    }

    public function testSmallFontSizeIsApplied(): void
    {
        $pdf = $this->generatePdf(3, ['size-footer-right' => 'xx-small']);

        self::assertMatchesRegularExpression('/\s6(\.0+)? Tf/', $pdf);
        self::assertMatchesRegularExpression('/\s10(\.0+)? Tf/', $pdf);
    }

    public function testCellValuesAreWrittenAndEscaped(): void
    {
        $pdf = $this->generatePdf(1, [], false, [['<b>R&D</b> é', '12,5']]);

        self::assertStringContainsString('<b>R&D</b>', $this->decode($pdf));
    }

    public function testHeadersAreRepeatedOnEveryPage(): void
    {
        $pdf = $this->generatePdf(150, [], false);

        self::assertSame($this->countPages($pdf), substr_count($pdf, '(Column)'));
    }

    public function testTotalsRowIsWritten(): void
    {
        $pdf = $this->generatePdf(3, [], false, null, ['Sum', '42']);

        self::assertStringContainsString('(Sum)', $pdf);
        self::assertStringContainsString('(42)', $pdf);
    }

    public function testPortraitIsTallerThanLandscape(): void
    {
        $landscape = $this->mediaBox($this->generatePdf(1, [], false));
        $portrait = $this->mediaBox($this->generatePdf(1, [], false, null, null, ['orientation' => 'portrait']));

        self::assertGreaterThan($landscape[1], $portrait[1]);
        self::assertLessThan($landscape[0], $portrait[0]);
    }

    public function testBandIsCenteredInTheMargin(): void
    {
        self::assertEqualsWithDelta(7.0, BandedTcpdf::getBandY(20.0), 0.001);
        self::assertSame(0.0, BandedTcpdf::getBandY(2.0));
    }

    public function testLayoutKeepsEveryHeaderWordWhole(): void
    {
        $layout = (new PdfWriter([]))->getLayout(['Id', 'Intitulécolonne'], [['1', 'x']], null, 200.0);

        self::assertGreaterThan($layout['widths'][0], $layout['widths'][1]);
        self::assertEqualsWithDelta(100.0, array_sum($layout['widths']), 0.1);
    }

    public function testLayoutCapsAVeryLongColumn(): void
    {
        $layout = (new PdfWriter([]))->getLayout(
            ['A', 'Url'],
            [['x', str_repeat('u', 500)]],
            null,
            200.0
        );

        self::assertLessThan(95.0, $layout['widths'][1]);
    }

    public function testLayoutShrinksTheFontOfAWideTable(): void
    {
        $headers = array_map(static fn(int $column): string => 'Intitulé ' . $column, range(1, 15));
        $wide = (new PdfWriter([]))->getLayout($headers, [array_fill(0, 15, 'valeur assez longue')], null, 190.0);
        $narrow = (new PdfWriter([]))->getLayout(['A', 'B'], [['x', 'y']], null, 190.0);

        self::assertLessThan($narrow['fontSize'], $wide['fontSize']);
        self::assertGreaterThanOrEqual(5.0, $wide['fontSize']);
    }

    public function testDataRowsHaveNoZebraOnTheFirstRow(): void
    {
        $zebra = '0.909804 0.909804 0.909804 rg';

        self::assertSame(0, substr_count($this->generatePdf(1, [], false), $zebra));
        self::assertSame(2, substr_count($this->generatePdf(2, [], false), $zebra));
    }

    private function generatePdf(
        int|null $rows,
        array $override = [],
        bool $withHeaderFooter = true,
        ?array $customRows = null,
        ?array $totals = null,
        array $params = [],
    ): string {
        $data = $customRows ?? array_map(
            static fn(int $row): array => ['Value ' . $row, (string)$row],
            range(1, (int)$rows)
        );
        $params += ['title' => 'Test'];
        if ($withHeaderFooter) {
            $params['header-footer'] = $override + self::HEADER_FOOTER;
        }

        return (new PdfWriter($params, false))->write(['Column', 'Number'], $data, $totals, ['left', 'right']);
    }

    private function decode(string $pdf): string
    {
        return preg_replace_callback(
            '/<([0-9A-Fa-f]+)>\s*Tj/',
            static fn(array $match): string => '(' . mb_convert_encoding((string)hex2bin($match[1]), 'UTF-8', 'UTF-16BE') . ') Tj',
            $pdf
        ) ?? $pdf;
    }

    /** @return array{float, float} */
    private function mediaBox(string $pdf): array
    {
        self::assertSame(
            1,
            preg_match('/\/MediaBox\s*\[\s*0(?:\.0+)?\s+0(?:\.0+)?\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdf, $match)
        );

        return [(float)$match[1], (float)$match[2]];
    }

    private function countPages(string $pdf): int
    {
        return (int)preg_match_all('/\/Type\s*\/Page\b/', $pdf);
    }
}
