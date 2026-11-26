<?php

namespace Lle\CruditBundle\Exporter\Pdf;

use Com\Tecnick\Pdf\Tcpdf;

class BandedTcpdf extends Tcpdf
{
    private const SIDES = ['left' => 'L', 'center' => 'C', 'right' => 'R'];
    private const DEFAULT_SIZE = 'medium';
    private const BAND_HEIGHT = 6.0;

    // CSS font-size keywords (the format the crud configs already use), converted to points.
    private const SIZES = [
        'xx-small' => 6.0,
        'x-small' => 7.0,
        'small' => 9.0,
        'medium' => 10.0,
        'large' => 12.0,
        'x-large' => 14.0,
        'xx-large' => 16.0,
    ];

    /**
     * @param array<string, string> $headerFooter
     */
    public function __construct(
        private readonly array $headerFooter,
        private readonly string $fontFamily = 'helvetica',
        bool $compress = true,
    ) {
        parent::__construct(compress: $compress);
        $this->enableDefaultPageContent(false);
    }

    public static function getBandY(float $margin): float
    {
        return max(0.0, ($margin - self::BAND_HEIGHT) / 2);
    }

    // The total of pages is only known once the body is rendered, so the bands are added last.
    public function addBands(): void
    {
        if (!array_filter($this->headerFooter)) {
            return;
        }

        $pageCount = count($this->page->getPages());
        foreach (range(0, $pageCount - 1) as $pageId) {
            $page = $this->page->getPage($pageId);
            $this->page->addContent(
                $this->getBand('header', $pageId, $pageCount, self::getBandY($page['margin']['PT'])),
                $pageId
            );
            $this->page->addContent(
                $this->getBand(
                    'footer',
                    $pageId,
                    $pageCount,
                    $page['height'] - $page['margin']['PB'] + self::getBandY($page['margin']['PB'])
                ),
                $pageId
            );
        }
    }

    private function getBand(string $band, int $pageId, int $pageCount, float $positionY): string
    {
        $page = $this->page->getPage($pageId);
        $margin = $page['margin']['PL'];
        $width = ($page['width'] - $margin - $page['margin']['PR']) / 3;
        $out = '';
        $positionX = $margin;
        foreach (self::SIDES as $side => $align) {
            $text = (string)($this->headerFooter[$band . '-' . $side] ?? '');
            if ($text !== '') {
                $size = self::SIZES[$this->headerFooter['size-' . $band . '-' . $side] ?? self::DEFAULT_SIZE]
                    ?? self::SIZES[self::DEFAULT_SIZE];
                $font = $this->font->insert($this->pon, $this->fontFamily, '', $size);
                $text = str_replace(
                    [PdfWriter::PAGE_NUMBER_PLACEHOLDER, PdfWriter::PAGE_COUNT_PLACEHOLDER],
                    [(string)($pageId + 1), (string)$pageCount],
                    $text
                );
                $out .= $font['out'] . $this->getTextCell($text, $positionX, $positionY, $width, self::BAND_HEIGHT, 0, 0, 'T', $align);
                $this->font->popLastFont();
            }
            $positionX += $width;
        }

        return $out;
    }
}
