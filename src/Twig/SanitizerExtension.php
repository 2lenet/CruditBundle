<?php

namespace Lle\CruditBundle\Twig;

use Lle\CruditBundle\Contracts\SanitizerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SanitizerExtension extends AbstractExtension
{
    private SanitizerInterface $sanitizer;

    public function __construct(SanitizerInterface $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('sanitize', [$this, 'sanitize'])
        ];
    }

    public function sanitize($html)
    {
        return $this->sanitizer->sanitize($html);
    }

}