<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\DataTransformer;

use Symfony\Component\Form\Extension\Core\DataTransformer\PercentToLocalizedStringTransformer;

/**
 * The transformer of PercentType with a given locale (Latin digits, LatinDigitsExtension): Symfony's takes the default
 * locale, without a way to give another one.
 */
class LatinPercentTransformer extends PercentToLocalizedStringTransformer
{
    public function __construct(
        private readonly string $locale,
        private readonly int $fractionDigits,
        ?string $type,
        private readonly int $rounding,
    ) {
        parent::__construct($fractionDigits, $type, $rounding);
    }

    protected function getNumberFormatter(): \NumberFormatter
    {
        $formatter = new \NumberFormatter($this->locale, \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $this->fractionDigits);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, $this->rounding);

        return $formatter;
    }
}
