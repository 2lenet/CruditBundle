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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class PdfExporter extends AbstractExporter
{
    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getSupportedFormat(): string
    {
        return Exporter::PDF;
    }

    public function export(iterable $resources, ExportParams $params): Response
    {
        $spreadsheet = new Spreadsheet();
        $this->pageSetup($spreadsheet);
        $sheet = $spreadsheet->getActiveSheet();
        $headersAdded = false;
        $row = 1;

        /** @var ResourceView $resource */
        foreach ($resources as $resource) {
            if ($params->getIncludeHeaders() && !$headersAdded) {
                $headers = $this->getHeaders($resource->getFields());
                foreach ($headers as $j => $header) {
                    $cell = Coordinate::stringFromColumnIndex($j + 1) . $row;
                    $sheet->setCellValue($cell, ' ' . $header);
                }
                $sheet->
                $row++;
                $headersAdded = true;
            }

            /** @var FieldView $field */
            foreach ($resource->getFields() as $j => $field) {
                $cell = Coordinate::stringFromColumnIndex($j + 1) . $row;

                $sheet->setCellValueExplicit($cell, ' ' . $this->getValue($field), $this->getType($field));
            }

            $row++;
        }

        foreach ($sheet->getColumnIterator("A", $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $writer = new Mpdf($spreadsheet);
        $writer->setEditHtmlCallback(
            function (string $html): string
            {
                $pagerepl = <<<EOF
@page page0 {
odd-header-name: html_myHeader1;
even-header-name: html_myHeader1;
odd-footer-name: html_myFooter2;
even-footer-name: html_myFooter2;

EOF;
                $html = preg_replace('/@page page0 {/', $pagerepl, $html);
                $bodystring = '/<body>/';
                $bodyrepl = <<<EOF
<body>
    <htmlpageheader name="myHeader1">
        <table width="100%">
            <tr>
                <td width="33%">Hotel Alpina Schiers</td>
                <td width="33%" align="center">Factures</td>
                <td width="33%" style="text-align: right;">{DATE Y-m-j}</td>
            </tr>
        </table>
    </htmlpageheader>

    <htmlpagefooter name="myFooter2">
        <table width="100%">
            <tr>
                <td width="33%">{DATE j/m/Y H:i} - dev@2le.net</td>
                <td width="33%" align="center">Arhon</td>
                <td width="33%" style="text-align: right;">Page {PAGENO} of {nbpg}</td>
            </tr>
        </table>
    </htmlpagefooter>

EOF;

                return preg_replace($bodystring, $bodyrepl, $html);
            }
        );

        $response = new StreamedResponse(function () use ($writer) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . urlencode('data.pdf') . '"');
            $writer->save('php://output');
        });


        $response->headers->set("Content-Type", "application/vnd.ms-excel");

        $filename = $params->getFilename() ?? "export";
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            "$filename.xls"
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

    public function pageSetup(Spreadsheet &$spreadsheet)
    {
        $spreadsheet->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Mark Baker")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription(
                "Test document for Office 2007 XLSX, generated using PHP classes."
            )
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        $spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.6);
        $spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.25);
        $spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.25);
        $spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.6);

        $spreadsheet->getActiveSheet()->setPrintGridlines(true);

        $spreadsheet->getActiveSheet()->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getActiveSheet()->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_A4);
    }

    public function getHeaderAndFooter()
    {
        //sortir la logique d'au dessus
    }

}
