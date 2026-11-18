<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Extension;

use Lle\CruditBundle\Form\Extension\SegmentedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * Short choices with one answer shown as a segmented control (option segmented, lot 3.2.b).
 */
class SegmentedExtensionTest extends TypeTestCase
{
    private const CHOICES = ['Small' => 's', 'Large' => 'l'];

    protected function getTypeExtensions(): array
    {
        return [new SegmentedExtension()];
    }

    public function testTheOptionIsGivenToTheView(): void
    {
        $segmented = $this->factory->create(ChoiceType::class, null, ['choices' => self::CHOICES, 'expanded' => true, 'segmented' => true])->createView();
        $list = $this->factory->create(ChoiceType::class, null, ['choices' => self::CHOICES, 'expanded' => true])->createView();

        self::assertTrue($segmented->vars['segmented']);
        self::assertFalse($list->vars['segmented'], 'radios by default');
    }

    public function testSeveralAnswersAreRefused(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"segmented" needs a choice with "expanded" and without "multiple"');
        $this->factory->create(ChoiceType::class, null, ['choices' => self::CHOICES, 'expanded' => true, 'multiple' => true, 'segmented' => true]);
    }

    public function testAListIsRefused(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory->create(ChoiceType::class, null, ['choices' => self::CHOICES, 'segmented' => true]);
    }
}
