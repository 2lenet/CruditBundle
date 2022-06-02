<?php

namespace Lle\CruditBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TelephoneFilterExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('telephone', [$this, 'formatTelephone'])
        ];
    }

    public function formatTelephone($telephone)
    {
        $substr = substr($telephone, 1);
        return str_replace(' ', '', '+33' . $substr);
    }
}
