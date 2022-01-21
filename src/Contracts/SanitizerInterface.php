<?php

namespace Lle\CruditBundle\Contracts;

interface SanitizerInterface
{
    /** prevent XSS attacks */
    public function sanitize(string $dirtyHtml): string;

}