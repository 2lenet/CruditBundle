<?php

namespace Lle\CruditBundle\Exporter;

use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Field\BooleanField;
use Lle\CruditBundle\Field\CurrencyField;
use Lle\CruditBundle\Field\IntegerField;
use Lle\CruditBundle\Field\NumberField;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExcelExporter extends AbstractExporter
{
    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getSupportedFormat(): string
    {
        return Exporter::EXCEL;
    }

    public function export(iterable $resources, ExportParams $params, array $total = []): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headersAdded = false;
        $row = 1;

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
                $cell = Coordinate::stringFromColumnIndex($j + 1) . $row;

                $sheet->setCellValueExplicit($cell, $this->getValue($field), $this->getType($field));
            }

            $row++;
        }

        foreach ($sheet->getColumnIterator("A", $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $writer = new Xls($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save("php://output");
        });

        $response->headers->set("Content-Type", "application/vnd.ms-excel");

        $filename = $params->getFilename() ?? "export";
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename . '_' . (new \DateTime())->format('YmdHis') . '.xls'
        );
        $response->headers->set("Content-Disposition", $disposition);

        return $response;
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
            "boolean", BooleanField::class => DataType::TYPE_BOOL,
            default => DataType::TYPE_STRING,
        };
    }
}
