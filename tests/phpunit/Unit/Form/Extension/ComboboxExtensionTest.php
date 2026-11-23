<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Extension;

use Lle\CruditBundle\Form\Extension\ComboboxExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * A choice in a list with search (option combobox, lot 3.2.c).
 */
class ComboboxExtensionTest extends TypeTestCase
{
    private const CHOICES = ['North' => 'n', 'South' => 's'];

    protected function getTypeExtensions(): array
    {
        return [new ComboboxExtension()];
    }

    public function testAListWithSeveralAnswersIsAListWithSearch(): void
    {
        $view = $this->factory->create(ChoiceType::class, null, ['choices' => self::CHOICES, 'multiple' => true])->createView();

        self::assertTrue($view->vars['combobox']);
    }

    public function testAListWithOneAnswerAndBoxesStayNative(): void
    {
        $one = $this->factory->create(ChoiceType::class, null, ['choices' => self::CHOICES])->createView();
        $boxes = $this->factory->create(ChoiceType::class, null, ['choices' => self::CHOICES, 'multiple' => true, 'expanded' => true])->createView();

        self::assertFalse($one->vars['combobox'], 'a list with one answer is a native list');
        self::assertFalse($boxes->vars['combobox']);
    }

    public function testTheOptionChoosesTheList(): void
    {
        $one = $this->factory->create(ChoiceType::class, null, ['choices' => self::CHOICES, 'combobox' => true])->createView();
        $several = $this->factory->create(ChoiceType::class, null, ['choices' => self::CHOICES, 'multiple' => true, 'combobox' => false])->createView();

        self::assertTrue($one->vars['combobox']);
        self::assertFalse($several->vars['combobox']);
    }

    public function testBoxesAreRefused(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"combobox" needs a choice without "expanded"');
        $this->factory->create(ChoiceType::class, null, ['choices' => self::CHOICES, 'expanded' => true, 'combobox' => true]);
    }
}
