<?php

namespace Lle\CruditBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * This filter allows to format the display of a phone number in +33..
 */
class CruditTelephoneCallFilterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('telephoneCall', [$this, 'formatTelephone']),
        ];
    }

    public function formatTelephone(?string $telephone): string
    {
        if ($telephone) {
            $telephone = str_replace(' ', '', $telephone);

            if (strlen($telephone) === 10 && $telephone[0] === '0') {
                $substr = substr($telephone, 1);

                return str_replace(' ', '', '+33' . $substr);
            }

            return $telephone;
        }

        return '';
    }
}
