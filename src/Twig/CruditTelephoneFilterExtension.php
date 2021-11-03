<?php

namespace Lle\CruditBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * This filter allows to format the display of a phone number in 03 88 .. .. ..
 */
class CruditTelephoneFilterExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('telephone', [$this, 'formatTelephone'])
        ];
    }

    public function formatTelephone($telephone)
    {
        $telephone = str_replace(' ', '', $telephone);
        
        if (strlen($telephone) == 10) {
            return wordwrap($telephone, 2, ' ', true);
        }

        return $telephone;
    }
}
