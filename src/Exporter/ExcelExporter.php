<?php

namespace Lle\CruditBundle\Exporter;

use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Field\BooleanField;
use Lle\CruditBundle\Field\CurrencyField;
use Lle\CruditBundle\Field\DateField;
use Lle\CruditBundle\Field\DateTimeField;
use Lle\CruditBundle\Field\IntegerField;
use Lle\CruditBundle\Field\NumberField;
use Lle\CruditBundle\Exception\ExporterException;
use Lle\CruditBundle\Field\TextAreaField;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExcelExporter extends AbstractExporter
{
    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

    public function getSupportedFormat(): string
    {
        return Exporter::EXCEL;
    }

    public function getContentType(): string
    {
        return 'application/vnd.ms-excel';
    }

    public function getFileExtension(): string
    {
        return '.xls';
    }

    public function export(iterable $resources, ExportParams $params, array $total = []): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headersAdded = false;
        $row = 1;

        // used to limit the width of large columns
        $largeColumns = [];

        /** @var ResourceView $resource */
        foreach ($resources as $resource) {
            if ($params->getIncludeHeaders() && !$headersAdded) {
                $headers = $this->getHeaders($resource->getFields());
                foreach ($headers as $j => $header) {
                    $cell = Coordinate::stringFromColumnIndex($j + 1) . $row;
                    $sheet->setCellValue($cell, $header);
                }
                $row++;
                $headersAdded = true;
            }

            /** @var FieldView $field */
            foreach ($resource->getFields() as $j => $field) {
                $column = Coordinate::stringFromColumnIndex($j + 1);
                $cell = $column . $row;

                if (
                    $field->getField()->getType() === TextAreaField::class
                    || $field->getField()->getType() === 'longtext'
                    || $field->getField()->getType() === 'text'
                ) {
                    // may be a large column, we fix its width.
                    $largeColumns[] = $column;
                }

                if (
                    $field->getField()->getType() === DateField::class
                    || $field->getField()->getType() === DateTimeField::class
                    || $field->getField()->getType() === 'date'
                    || $field->getField()->getType() === 'datetime'
                ) {
                    if ($field->getValue()) {
                        // Showing format
                        $format = $this->convertFormat($field->getOptions()['format']);
                        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($format);

                        // Value format
                        if ($field->getRawValue() instanceof \DateTimeInterface) {
                            $value = $field->getRawValue()->format('Y-m-d H:i:s');
                        } else {
                            $value = $this->getValue($field);
                        }

                        $sheet->setCellValueExplicit(
                            $cell,
                            $value,
                            $this->getType($field)
                        );
                    }
                } else {
                    $sheet->setCellValueExplicit($cell, $this->getValue($field), $this->getType($field));
                }
            }

            $row++;
        }

        $largeColumns = array_unique($largeColumns);

        foreach ($sheet->getColumnIterator("A", $sheet->getHighestColumn()) as $column) {
            if (in_array($column->getColumnIndex(), $largeColumns)) {
                $sheet->getColumnDimension($column->getColumnIndex())->setWidth(80);
            } else {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }
        }

        $path = tempnam(sys_get_temp_dir(), Exporter::EXCEL);
        if ($path === false) {
            throw new ExporterException('Unknown EXCEL exporter error');
        }

        $writer = new Xls($spreadsheet);
        $writer->save($path);

        return $path;
    }

    protected function convertFormat(string $format): string
    {
        $pattern = [
            '/y/',
            '/Y/',
            '/m/',
            '/d/',
            '/H/',
            '/i/',
            '/s/',
        ];
        $remplacement = [
            'yyyy',
            'yyyy',
            'mm',
            'dd',
            'hh',
            'mm',
            'ss',
        ];

        return (string)preg_replace($pattern, $remplacement, $format);
    }

    protected function getHeaders(array $fields): array
    {
        $result = [];

        /** @var FieldView $field */
        foreach ($fields as $field) {
            $result[] = $this->translator->trans($field->getField()->getLabel());
        }

        return $result;
    }

    protected function getType(FieldView $field): string
    {
        if ($field->getRawValue() === null || $field->getRawValue() === "") {
            return DataType::TYPE_NULL;
        }

        return match ($field->getField()->getType()) {
            "bigint", "smallint", "float", "integer", NumberField::class, CurrencyField::class, IntegerField::class
            => DataType::TYPE_NUMERIC,
            "boolean", BooleanField::class
            => DataType::TYPE_BOOL,
            'date', 'datetime', DateField::class, DateTimeField::class
            => DataType::TYPE_ISO_DATE,
            default
            => DataType::TYPE_STRING,
        };
    }
}
