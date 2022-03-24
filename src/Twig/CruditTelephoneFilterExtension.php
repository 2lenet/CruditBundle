<?php

namespace Lle\CruditBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * This filter allows to format the display of a phone number in 03 88 .. .. ..
 */
class CruditTelephoneFilterExtension extends AbstractExtension
{
    private const INDICATOR_MAX_LENGTH = 3;

    // [indicator => [ length => format]]
    // source: https://fr.wikipedia.org/wiki/Num%C3%A9ro_de_t%C3%A9l%C3%A9phone
    private const FORMATS = [
        "+1" => [
            "10" => "## (###) ###-####",
        ],
        "+212" => [
            "9" => "#### # ## ## ## ##"
        ],
        "+213" => [
            "8" => "#### (##) ### ###",
            "9" => "#### (##) ### ####",
            "10" => "#### (##) ### #####",
        ],
        "+216" => [
            "9" => "#### ## ### ###"
        ],
        "+225" => [
            "10" => "(####) ##.##.##.##.##"
        ],
        "+228" => [
            "9" => "#### ## ## ## ##"
        ],
        "+237" => [
            "9" => "#### ### ## ## ##"
        ],
        "+242" => [
            "9" => "#### ## ### ####"
        ],
        "+262" => [
            "9" => "#### ### ## ## ##"
        ],
        "+32" => [
            "8" => "### ## ## ## ##"
        ],
        "+33" => [
            "9" => "### # ## ## ## ##"
        ],
        "+34" => [
            "9" => "### ###.##.##.##"
        ],
        "+352" => [
            "5" => "#### # ####",
            "6" => "#### ## ####",
            "8" => "#### #### ####",
            "9" => "#### ### ### ###"
        ],
        "+353" => [
            "8" => "#### # ### ####"
        ],
        "+41" => [
            "9" => "### ## ### ## ##"
        ],
        "+49" => [
            "9" => "### # ## ## ## ##",
            "10" => "### ## #### ####",
            "11" => "### ## #### #####",
            "12" => "### ## #### ######",
        ],
        "+509" => [
            "8" => "#### #### ####"
        ],
        "+590" => [
            "9" => "#### ### ## ## ##"
        ],
        "+596" => [
            "9" => "#### ### ## ## ##"
        ],
        "+687" => [
            "9" => "#### ### ## ## ##"
        ],
        "+689" => [
            "8" => "#### ## ### ###"
        ],
    ];

    public function getFilters()
    {
        return [
            new TwigFilter('telephone', [$this, 'formatTelephone'])
        ];
    }

    public function formatTelephone($telephone)
    {
        $telephone = str_replace(' ', '', $telephone);

        $mask = null;
        if (strpos($telephone, '+') === 0) {
            $lenTel = strlen($telephone);
            $i = self::INDICATOR_MAX_LENGTH + 1;
            while ($mask == null && $i > 0) {
                $indicator = substr($telephone, 0, $i);
                $currentLen = (string)($lenTel - $i);
                if (key_exists($indicator, self::FORMATS) && key_exists($currentLen, self::FORMATS[$indicator])) {
                    $mask = self::FORMATS[$indicator][$currentLen];
                }
                $i--;
            }
        } elseif (strlen($telephone) == 10) {
            $mask = "## ## ## ## ##";
        }
        return ($mask != null ? $this->applyMask($mask, $telephone) : $telephone);
    }

    private function applyMask(string $mask, string $value)
    {
        $result = "";
        $i = 0;
        $counter = 0;
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
