<?php

declare(strict_types=1);

namespace phpunit\Unit\Exporter;

use Lle\CruditBundle\Builder\ExportViewBuilder;
use Lle\CruditBundle\Contracts\DescribedExporterInterface;
use Lle\CruditBundle\Exporter\CsvExporter;
use Lle\CruditBundle\Exporter\DownloadCookie;
use Lle\CruditBundle\Exporter\ExcelExporter;
use Lle\CruditBundle\Exporter\Exporter;
use Lle\CruditBundle\Exporter\ExportParams;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The dialog of the export (lot 3.1.g): the formats it offers and the CSV that Excel reads.
 */
class ExportDialogTest extends TestCase
{
    private function translator(array $messages = []): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $parameters = [], ?string $domain = null): string => $messages[$domain . '|' . $id] ?? $id
        );

        return $translator;
    }

    private function exporters(): Exporter
    {
        $translator = $this->translator();
        $project = new class () implements DescribedExporterInterface {
            public function getSupportedFormat(): string
            {
                return 'json';
            }

            public function getFileExtension(): string
            {
                return '.json';
            }

            public function export(iterable $resources, ExportParams $params, array $totals = []): string
            {
                return '';
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
        };

        return new Exporter([new CsvExporter($translator), new ExcelExporter($translator), $project]);
    }

    private function builder(array $messages = []): ExportViewBuilder
    {
        return new ExportViewBuilder($this->exporters(), $this->translator($messages));
    }

    public function testExporterListsTheFormatsOfAllTheExporters(): void
    {
        self::assertSame(['csv', 'xls', 'json'], $this->exporters()->getFormats());
        self::assertNull($this->exporters()->findExporter('doc'));
    }

    public function testWithoutConfigAllTheExportersAreOffered(): void
    {
        $result = $this->builder()->build([], null);

        self::assertSame(['csv', 'xls', 'json'], array_column($result['formats'], 'value'));
    }

    public function testTheConfigOfTheActionChoosesTheFormatsAndTheirOrder(): void
    {
        $result = $this->builder()->build(['export' => ['xls', 'csv', 'xls']], null);

        self::assertSame(['xls', 'csv'], array_column($result['formats'], 'value'));
    }

    public function testAFormatOfTheBundleIsTranslatedWithItsIcon(): void
    {
        $result = $this->builder([
            'LleCruditBundle|crudit.export.format.xls.label' => 'Excel',
            'LleCruditBundle|crudit.export.format.xls.description' => 'A workbook',
        ])->build(['export' => ['xls']], null);

        self::assertSame(['value' => 'xls', 'label' => 'Excel', 'description' => 'A workbook', 'icon' => 'file-excel'], $result['formats'][0]);
    }

    public function testADescribedExporterDescribesItself(): void
    {
        $result = $this->builder()->build(['export' => ['json']], null);

        self::assertSame(['value' => 'json', 'label' => 'JSON', 'description' => 'For other programs', 'icon' => 'file'], $result['formats'][0]);
    }

    public function testAFormatOfTheOldKeyOfTheListKeepsItsLabel(): void
    {
        $result = $this->builder(['clients|export.xml' => 'XML (Sage)'])->build(['export' => ['xml']], 'clients');

        self::assertSame('XML (Sage)', $result['formats'][0]['label']);
        self::assertNull($result['formats'][0]['description']);
    }

    public function testAFormatWithoutAnyTextShowsItsCodeInCapitals(): void
    {
        $result = $this->builder()->build(['export' => ['xml']], 'clients');

        self::assertSame('XML', $result['formats'][0]['label']);
    }

    public function testTheCsvStartsWithAByteOrderMarkSoThatExcelReadsTheAccents(): void
    {
        $path = (new CsvExporter($this->translator()))->export([], new ExportParams());

        self::assertSame("\xEF\xBB\xBF", file_get_contents($path));
        unlink($path);
    }

    public function testTheByteOrderMarkCanBeLeftOut(): void
    {
        $path = (new CsvExporter($this->translator()))->export([], ExportParams::new()->setBom(false));

        self::assertSame('', file_get_contents($path));
        unlink($path);
    }

    public function testTheDownloadCookieIsReadableByTheScriptAtTheRoot(): void
    {
        $response = new Response();
        DownloadCookie::attach(Request::create('/client/export', 'GET', ['download_token' => '0a1b2c3d-4e5f']), $response);

        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);
        self::assertSame('crudit_export', $cookies[0]->getName());
        self::assertSame('0a1b2c3d-4e5f', $cookies[0]->getValue());
        self::assertFalse($cookies[0]->isHttpOnly(), 'the script of the dialog reads it');
        self::assertSame('/', $cookies[0]->getPath(), 'the script deletes it there');
        self::assertSame(Cookie::SAMESITE_LAX, $cookies[0]->getSameSite());
    }

    /**
     * @dataProvider invalidTokens
     */
    public function testNoCookieForATokenThatIsNotAPlainIdentifier(mixed $token): void
    {
        $response = new Response();
        DownloadCookie::attach(Request::create('/client/export', 'GET', ['download_token' => $token]), $response);

        self::assertSame([], $response->headers->getCookies());
    }

    public static function invalidTokens(): iterable
    {
        yield 'too short' => ['abc'];
        yield 'too long' => [str_repeat('a', 65)];
        yield 'a semicolon' => ['abcdefgh;path=/evil'];
        yield 'a space' => ['abcdefgh ijkl'];
        yield 'an array' => [['abcdefgh']];
    }

    public function testNoCookieWithoutAToken(): void
    {
        $response = new Response();
        DownloadCookie::attach(Request::create('/client/export'), $response);

        self::assertSame([], $response->headers->getCookies());
    }
}
