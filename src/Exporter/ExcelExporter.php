<?php


namespace Lle\CruditBundle\Exporter;


use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\ResourceView;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExporter implements ExporterInterface
{
    public function getSupportedFormat(): string
    {
        return Exporter::EXCEL;
    }

    public function export($resources, $format): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        /** @var ResourceView $resource */
        foreach ($resources as $i => $resource) {
            /** @var FieldView $field */
            foreach ($resource->getFields() as $j => $field) {

                $cell = Coordinate::stringFromColumnIndex($j + 1) . ($i + 1);

                $sheet->setCellValue($cell, trim((string)$field->getValue()));
            }
        }

        foreach ($sheet->getColumnIterator("A", $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $writer = new Xls($spreadsheet);
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save("php://output");
        });

        $response->headers->set("Content-Type", "application/vnd.ms-excel");

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            "export.xls"
        );
        $response->headers->set("Content-Disposition", $disposition);

        return $response;
    }
}
