<?php

namespace Lle\CruditBundle\Service;

use HTMLPurifier;
use HTMLPurifier_Config;
use Lle\CruditBundle\Contracts\SanitizerInterface;

class SanitizerService implements SanitizerInterface
{
    public function sanitize(?string $dirtyHtml = ""): string
    {
        if ($dirtyHtml) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Cache.DefinitionImpl', null);
            $sanitizer = new HTMLPurifier($config);

            return $sanitizer->purify($dirtyHtml);
        }

        return '';
    }
}
