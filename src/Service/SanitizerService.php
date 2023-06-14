<?php

namespace Lle\CruditBundle\Service;

use Lle\CruditBundle\Contracts\SanitizerInterface;
use HTMLPurifier;
use HTMLPurifier_Config;

class SanitizerService implements SanitizerInterface
{
    public function sanitize(?string $dirtyHtml = ""): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $sanitizer = new HTMLPurifier($config);

        return $sanitizer->purify($dirtyHtml);
    }
}
