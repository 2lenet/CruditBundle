<?php

namespace Lle\CruditBundle\Exporter\Pdf;

use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf as DompdfWriter;

class PdfWriter extends DompdfWriter
{
    protected function createExternalWriterInstance(): PageCountDompdf
    {
        return new PageCountDompdf();
    }
}
