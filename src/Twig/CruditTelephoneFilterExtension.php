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

    /**
     * Format phone number :
     *  0102030405 => 01 02 03 04 05
     *  +X102030405 => +X 1 02 03 04 05
     *  +XX102030405 => +XX 1 02 03 04 05
     *  +XXX102030405 => +XXX 1 02 03 04 05
     *
     * @param $telephone
     * @return string
     */
    public function formatTelephone($telephone)
    {
        $telephone = str_replace(' ', '', $telephone);

        if (strpos($telephone, '+') === 0) {
            $telephone = substr($telephone, 1);
            $indicatorMask = str_repeat("#", strlen($telephone) - 9);
            $telephone = "+" . $this->applyMask($indicatorMask . " # ## ## ## ##", $telephone);
        } else {
            $telephone = $this->applyMask("## ## ## ## ##", $telephone);
        }

        return $telephone;
    }

    private function applyMask(string $mask, string $value)
    {
        $result = ""; $i = 0; $counter = 0;
        $lenValue = strlen($value);
        $lenMask = strlen($mask);
        while ($counter < $lenValue && $i < $lenMask) {
            $char = $mask[$i];
            $result .= ($char === '#') ? $value[$counter++] : $char;
            $i++;
        }
        return $result;
    }
}
