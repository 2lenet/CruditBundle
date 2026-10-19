<?php

namespace phpunit\Unit\Form\Type;

use Lle\CruditBundle\Form\Extension\TomSelectExtension;
use Lle\CruditBundle\Form\Type\TelephoneType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class TelephoneTypeTest extends TypeTestCase
{
    private string $locale;

    protected function getTypeExtensions(): array
    {
        return [new TomSelectExtension()];
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Country labels come from Intl in the current locale
        $this->locale = \Locale::getDefault();
        \Locale::setDefault('en');
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->locale);
        parent::tearDown();
    }

    public function testSubmitValidNumber(): void
    {
        $form = $this->factory->create(TelephoneType::class);
        $form->submit(['country' => 'CH', 'number' => '079 123 45 67']);

        self::assertTrue($form->isSynchronized());
        self::assertSame('+41791234567', $form->getData());
    }

    public function testSubmitInvalidNumber(): void
    {
        $form = $this->factory->create(TelephoneType::class);
        $form->submit(['country' => 'CH', 'number' => '079 123']);

        self::assertFalse($form->isSynchronized());
        self::assertSame('crudit.telephone.invalid_length', $form->getTransformationFailure()?->getInvalidMessage());
        self::assertSame('+41', $form->getTransformationFailure()->getInvalidMessageParameters()['{{ code }}']);
    }

    public function testSubmitEmptyNumber(): void
    {
        $form = $this->factory->create(TelephoneType::class, null, ['required' => false]);
        $form->submit(['country' => 'FR', 'number' => '']);

        self::assertTrue($form->isSynchronized());
        self::assertNull($form->getData());
    }

    public function testInitialDataIsSplit(): void
    {
        $form = $this->factory->create(TelephoneType::class, '+33612345678', ['default_country' => 'CH']);

        self::assertSame('FR', $form->get('country')->getData());
        self::assertSame('612345678', $form->get('number')->getData());
    }

    public function testCountryChoicesShowCallingCode(): void
    {
        $view = $this->factory->create(TelephoneType::class)->createView();
        $labels = array_map(fn ($choice) => $choice->label, $view['country']->vars['choices']);

        self::assertContains('Switzerland (+41)', $labels);
    }

    public function testCountryChoicesExposeCallingCode(): void
    {
        $view = $this->factory->create(TelephoneType::class, null, ['preferred_countries' => ['CH']])->createView();

        $preferred = array_values($view['country']->vars['preferred_choices']);

        self::assertSame('+41', $preferred[0]->attr['data-code']);
    }

    public function testCountrySelectIsNotTomSelect(): void
    {
        $view = $this->factory->create(TelephoneType::class)->createView();

        self::assertStringNotContainsString(
            TomSelectExtension::TOMSELECT_CLASSNAME,
            $view['country']->vars['attr']['class'],
        );
    }

    public function testUnknownDefaultCountryIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory->create(TelephoneType::class, null, ['default_country' => 'ZZ']);
    }
}
