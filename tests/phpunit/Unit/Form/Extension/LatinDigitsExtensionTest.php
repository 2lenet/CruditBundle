<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Extension;

use Lle\CruditBundle\Form\Extension\LatinDigitsExtension;
use Lle\CruditUiBundle\Format\Formatter;
use Lle\CruditUiBundle\Locale\RequestLocale;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Numbers typed as text in Latin digits whatever the language (lot 3.2.b): ar_SA and fa write their own digits.
 */
class LatinDigitsExtensionTest extends TypeTestCase
{
    private string $defaultLocale;

    protected function setUp(): void
    {
        $this->defaultLocale = \Locale::getDefault();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->defaultLocale);
        parent::tearDown();
    }

    protected function getTypeExtensions(): array
    {
        return [new LatinDigitsExtension($this->formatter('ar_SA'))];
    }

    public function testALanguageWithItsOwnDigitsShowsAndReadsLatinDigits(): void
    {
        \Locale::setDefault('ar_SA');
        foreach ([NumberType::class => ['scale' => 1], MoneyType::class => [], PercentType::class => ['scale' => 1], IntegerType::class => ['grouping' => true]] as $type => $options) {
            $form = $this->factory->create($type, 12, $options);
            self::assertDoesNotMatchRegularExpression('/[٠-٩]/u', (string) $form->createView()->vars['value'], $type);

            $form = $this->factory->create($type, null, $options);
            $form->submit('12');
            self::assertTrue($form->isSynchronized(), $type . ': Latin digits are refused');
            self::assertEquals($type === PercentType::class ? 0.12 : 12, $form->getData(), $type);
        }
    }

    public function testANumberTypedInArabicDigitsIsRefused(): void
    {
        \Locale::setDefault('ar_SA');
        $form = $this->factory->create(NumberType::class, null, ['scale' => 1]);
        $form->submit('١٢٫٥');

        self::assertFalse($form->isSynchronized());
        self::assertNull($form->getData());
    }

    public function testALanguageWithLatinDigitsIsLeftToSymfony(): void
    {
        \Locale::setDefault('fr_FR');
        $form = $this->factory->create(NumberType::class, null, ['scale' => 1]);
        $form->submit('12,5');

        self::assertSame(12.5, $form->getData());
        self::assertTrue(LatinDigitsExtension::latinDigits('fr_FR'));
        self::assertTrue(LatinDigitsExtension::latinDigits('ar_MA'));
        self::assertFalse(LatinDigitsExtension::latinDigits('ar_SA'));
        self::assertFalse(LatinDigitsExtension::latinDigits('fa'));
    }

    private function formatter(string $locale): Formatter
    {
        $request = new Request();
        $request->setLocale($locale);
        $stack = new RequestStack();
        $stack->push($request);

        return new Formatter(new RequestLocale($stack, $locale), new Environment(new ArrayLoader()));
    }
}
