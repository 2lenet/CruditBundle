<?php

declare(strict_types=1);

namespace phpunit\Unit\Field;

use Lle\CruditBundle\Field\AbstractField;
use Lle\CruditBundle\Field\CurrencyField;
use Lle\CruditBundle\Field\DateField;
use Lle\CruditBundle\Field\DateTimeField;
use Lle\CruditBundle\Field\IntegerField;
use Lle\CruditBundle\Field\NumberField;
use Lle\CruditUiBundle\Twig\FormatExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use Twig\Extension\AttributeExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFunction;

/**
 * Dates and numbers of the fields follow the locale of the request, in Latin digits; a format or separators given
 * by the project are kept.
 */
#[RequiresPhpExtension('intl')]
class FieldFormatsTest extends TestCase
{
    private const string NNBSP = "\u{202F}";

    private const array TEMPLATES = [
        DateField::class => 'date',
        DateTimeField::class => 'datetime',
        NumberField::class => 'number',
        IntegerField::class => 'integer',
        CurrencyField::class => 'currency',
    ];

    private string $defaultLocale;

    protected function setUp(): void
    {
        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown(): void
    {
        // Request::setLocale() changes the default locale of PHP
        \Locale::setDefault($this->defaultLocale);
    }

    /**
     * @return iterable<string, array{class-string<AbstractField>, string, mixed, array<string, mixed>, string}>
     */
    public static function fields(): iterable
    {
        $date = new \DateTimeImmutable('2026-10-13 09:05:00');

        yield 'date, fr' => [DateField::class, 'fr', $date, [], '13/10/2026'];
        yield 'date, en' => [DateField::class, 'en', $date, [], '10/13/26'];
        yield 'date, ar_SA: Latin digits' => [DateField::class, 'ar_SA', $date, [], "13\u{200F}/10\u{200F}/2026"];
        yield 'date, format of the project' => [DateField::class, 'en', $date, ['format' => 'd/m/Y'], '13/10/2026'];
        yield 'datetime, fr' => [DateTimeField::class, 'fr', $date, [], '13/10/2026 09:05'];
        yield 'datetime, en' => [DateTimeField::class, 'en', $date, [], '10/13/26, 9:05' . self::NNBSP . 'AM'];
        yield 'datetime, format of the project' => [DateTimeField::class, 'en', $date, ['format' => 'd/m/Y H:i'], '13/10/2026 09:05'];
        yield 'number, fr' => [NumberField::class, 'fr', 1234.567, [], '1' . self::NNBSP . '234,57'];
        yield 'number, en' => [NumberField::class, 'en', 1234.5, [], '1,234.50'];
        yield 'number, ar_SA: Latin digits' => [NumberField::class, 'ar_SA', 1234.5, [], '1,234.50'];
        yield 'number, ar_MA: separators of Morocco' => [NumberField::class, 'ar_MA', 1234.5, [], '1.234,50'];
        yield 'number, separators of the project' => [NumberField::class, 'en', 1234.5, ['decimal_separator' => ',', 'thousands_separator' => ' '], '1 234,50'];
        yield 'number, one separator of the project' => [NumberField::class, 'en', 1234.5, ['thousands_separator' => '.'], '1.234,50'];
        yield 'number, decimals' => [NumberField::class, 'en', 1234.6, ['decimals' => 0], '1,235'];
        yield 'integer, fr' => [IntegerField::class, 'fr', 1234, [], '1' . self::NNBSP . '234'];
        yield 'integer, en' => [IntegerField::class, 'en', 1234, [], '1,234'];
        yield 'integer, separator of the project' => [IntegerField::class, 'en', 1234, ['thousands_separator' => ' '], '1 234'];
        yield 'currency, fr' => [CurrencyField::class, 'fr', 1234.5, [], '1' . self::NNBSP . "234,50\u{A0}€"];
        yield 'currency, ar_SA: Latin digits' => [CurrencyField::class, 'ar_SA', 1234.5, [], "\u{200F}1,234.50\u{A0}€"];
        yield 'currency, locale of the project' => [CurrencyField::class, 'en', 1234.5, ['locale' => 'fr'], '1' . self::NNBSP . "234,50\u{A0}€"];
    }

    /**
     * @param class-string<AbstractField> $class
     * @param array<string, mixed>        $options
     */
    #[DataProvider('fields')]
    public function testFormat(string $class, string $locale, mixed $value, array $options, string $expected): void
    {
        $twig = $this->twig($locale);
        $field = new $class($twig, new ParameterBag([
            'lle_crudit.css_class_columns_show' => '',
            'lle_crudit.css_class_columns_card' => '',
            'lle_crudit.default_number_alignment' => 'right',
            'lle_crudit.default_integer_alignment' => 'right',
            'lle_crudit.default_currency_alignment' => 'right',
        ]));
        $resolver = new OptionsResolver();
        $field->configureOptions($resolver);

        $html = $twig->render('@LleCrudit/field/' . self::TEMPLATES[$class] . '.html.twig', [
            'value' => $value,
            'options' => $resolver->resolve($options),
            'view' => ['field' => ['editInPlace' => false, 'options' => []]],
            'resource' => null,
        ]);

        self::assertSame($expected, trim(strip_tags($html)));
    }

    private function twig(string $locale): Environment
    {
        $request = new Request();
        $request->setLocale($locale);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $formatExtension = new FormatExtension($requestStack, 'en');

        $loader = new FilesystemLoader();
        $loader->addPath(\dirname(__DIR__, 4) . '/src/Resources/views', 'LleCrudit');
        $twig = new Environment($loader, ['strict_variables' => true]);
        $twig->addExtension(new IntlExtension());
        $twig->addExtension(new AttributeExtension(FormatExtension::class));
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([FormatExtension::class => static fn () => $formatExtension]));
        // Functions of the edit in place branch, not rendered here
        $twig->addFunction(new TwigFunction('path', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));

        return $twig;
    }
}
