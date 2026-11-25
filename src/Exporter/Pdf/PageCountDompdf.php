<?php

namespace Lle\CruditBundle\Exporter\Pdf;

use Dompdf\Dompdf;

/**
 * Dompdf has no CSS counter for the total number of pages, so the document is rendered a first
 * time to count them, then once more with the placeholder replaced.
 */
class PageCountDompdf extends Dompdf
{
    public const PAGE_COUNT_PLACEHOLDER = '{nbpg}';

    private const OPTIONS = ['defaultFont' => 'DejaVu Sans'];

    private string $rawHtml = '';

    public function __construct()
    {
        parent::__construct(self::OPTIONS);
    }

    public function loadHtml($str, $encoding = null)
    {
        $this->rawHtml = (string)$str;

        parent::loadHtml($str, $encoding);
    }

    public function render()
    {
        if (str_contains($this->rawHtml, self::PAGE_COUNT_PLACEHOLDER)) {
            parent::loadHtml(str_replace(self::PAGE_COUNT_PLACEHOLDER, (string)$this->countPages(), $this->rawHtml));
        }

        parent::render();
    }

    // A Dompdf instance cannot render twice (its frame tree is consumed), hence a throwaway one.
    private function countPages(): int
    {
        $counter = new Dompdf(self::OPTIONS);
        $counter->setPaper($this->getPaperSize(), $this->getPaperOrientation());
        $counter->loadHtml($this->rawHtml);
        $counter->render();

        return $counter->getCanvas()->get_page_count();
    }
}
