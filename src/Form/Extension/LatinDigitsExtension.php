<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Extension;

use Lle\CruditBundle\Form\DataTransformer\LatinPercentTransformer;
use Lle\CruditUiBundle\Format\Formatter;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Numbers typed as text in Latin digits, whatever the language (lot 3.2.b, as the values shown since lot 1.4): in
 * ar_SA or fa, Symfony writes ١٬٢٣٤٫٥ and refuses 1234.5. The transformer of the type is given the locale of the
 * request with the Latin digits (Formatter::locale() of CruditUiBundle, `ar_SA@numbers=latn;…`), only for a language
 * whose digits are not Latin; a number typed in Arabic or Persian digits is refused. The default locale of PHP is
 * left alone: Symfony Intl does not read `ar@numbers=latn` or `fa@numbers=latn` (country names). A number with html5 (or an
 * integer without grouping) is already written in "en" by Symfony.
 */
class LatinDigitsExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly Formatter $formatter,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [NumberType::class, IntegerType::class, MoneyType::class, PercentType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (($options['html5'] ?? false) || self::latinDigits(\Locale::getDefault())) {
            return;
        }
        $type = $builder->getType()->getInnerType();
        $locale = $this->formatter->locale();

        $transformer = match (true) {
            $type instanceof MoneyType => new MoneyToLocalizedStringTransformer($options['scale'], $options['grouping'], $options['rounding_mode'], $options['divisor'], $locale, $options['input']),
            $type instanceof PercentType => new LatinPercentTransformer($locale, $options['scale'] ?? 0, $options['type'], $options['rounding_mode']),
            $type instanceof IntegerType => $options['grouping'] ? new IntegerToLocalizedStringTransformer(true, $options['rounding_mode'], $locale) : null,
            $type instanceof NumberType => new NumberToLocalizedStringTransformer($options['scale'], $options['grouping'], $options['rounding_mode'], $locale),
            default => null,
        };
        if ($transformer === null) {
            return;
        }
        $builder->resetViewTransformers();
        $builder->addViewTransformer($transformer);
    }

    /** Whether the numbers of the locale are written in Latin digits (fr, en, ar_MA…), not ar_SA, fa… */
    public static function latinDigits(string $locale): bool
    {
        $written = (string) (new \NumberFormatter($locale, \NumberFormatter::DECIMAL))->format(1234567890);

        return preg_match('/\p{Nd}/u', (string) preg_replace('/[0-9]/', '', $written)) === 0;
    }
}
