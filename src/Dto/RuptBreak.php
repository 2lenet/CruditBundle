<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

final readonly class RuptBreak
{
    public function __construct(
        public string $display,
        public string $key,
        public string $cssClass = '',
    ) {
    }
}
