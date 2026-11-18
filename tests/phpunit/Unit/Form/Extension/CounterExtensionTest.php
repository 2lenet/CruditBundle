<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Extension;

use Lle\CruditBundle\Form\Extension\CounterExtension;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * The counter of characters asked or taken away (option counter, lot 3.2.b): the variable of the theme of CruditUiBundle.
 */
class CounterExtensionTest extends TypeTestCase
{
    protected function getTypeExtensions(): array
    {
        return [new CounterExtension()];
    }

    public function testTheOptionIsGivenToTheViewOnlyWhenSet(): void
    {
        self::assertTrue($this->factory->create(TextType::class, null, ['counter' => true])->createView()->vars['counter']);
        self::assertFalse($this->factory->create(TextareaType::class, null, ['counter' => false])->createView()->vars['counter']);
        self::assertArrayNotHasKey('counter', $this->factory->create(TextareaType::class)->createView()->vars, 'the theme decides');
    }
}
