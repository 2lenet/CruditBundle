<?php

namespace phpunit\Unit\Exporter;

use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Exporter\ExportParams;
use Lle\CruditBundle\Exporter\Pdf\PdfWriter;
use Lle\CruditBundle\Exporter\PdfExporter;
use Lle\CruditBundle\Field\IntegerField;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PdfExporterExportTest extends TestCase
{
    public function testExportWritesFormattedValuesAndTotals(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn(string $id): string => strtoupper($id));

        $quantity = Field::new('quantity', IntegerField::class);
        $amount = Field::new('amount', 'decimal');
        $resources = [new ResourceView(1, new \stdClass(), [
            $this->fieldView($quantity, 1234, '1234'),
            $this->fieldView($amount, '1234.5', '1234.5'),
        ])];
        $totals = [['field' => $this->fieldView($amount, '1234.5', '1234.5'), 'total' => 1234.5]];

        $exporter = new class ($translator) extends PdfExporter {
            protected function createWriter(array $pdfParams): PdfWriter
            {
                return new PdfWriter($pdfParams, false);
            }
        };
        $path = $exporter->export($resources, ExportParams::new(), $totals);
        $pdf = (string)file_get_contents($path);
        unlink($path);

        self::assertStringStartsWith('%PDF', $pdf);
        self::assertStringContainsString('(FIELD.QUANTITY)', $pdf);
        self::assertStringContainsString('(1.234)', $pdf);
        self::assertStringContainsString('(1.234,50)', $pdf);
        self::assertSame(2, substr_count($pdf, '(1.234,50)'));
    }

    public function testFalseBooleanIsWrittenAsNo(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn(string $id): string => $id);
        $paid = Field::new('paid', 'boolean');
        $resources = [
            new ResourceView(1, new \stdClass(), [$this->fieldView($paid, true, '1')]),
            new ResourceView(2, new \stdClass(), [$this->fieldView($paid, false, '')]),
            new ResourceView(3, new \stdClass(), [$this->fieldView($paid, null, '')]),
        ];
        $exporter = new class ($translator) extends PdfExporter {
            protected function createWriter(array $pdfParams): PdfWriter
            {
                return new PdfWriter($pdfParams, false);
            }
        };

        $path = $exporter->export($resources, ExportParams::new());
        $pdf = (string)file_get_contents($path);
        unlink($path);

        self::assertSame(1, substr_count($pdf, '(crudit.boolean.yes)'));
        self::assertSame(1, substr_count($pdf, '(crudit.boolean.no)'));
    }

    private function fieldView(Field $field, mixed $raw, string $string): FieldView
    {
        $view = new FieldView($field, $raw);
        $view->setStringValue($string);
        $view->setOptions($field->getOptions());

        return $view;
    }
}
