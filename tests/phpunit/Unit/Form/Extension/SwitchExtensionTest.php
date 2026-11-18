<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Extension;

use Lle\CruditBundle\Form\Extension\SwitchExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * A checkbox shown as a switch (option switch, lot 3.2.b): the variable switch, made role="switch" by the theme of Crudit.
 */
class SwitchExtensionTest extends TypeTestCase
{
    protected function getTypeExtensions(): array
    {
        return [new SwitchExtension()];
    }

    public function testASwitchIsToldToTheThemeAndKeepsTheAttributesOfTheField(): void
    {
        $view = $this->factory->create(CheckboxType::class, null, ['switch' => true, 'attr' => ['class' => 'mine']])->createView();

        self::assertTrue($view->vars['switch']);
        self::assertSame(['class' => 'mine'], $view->vars['attr'], 'the role is set by the theme of Crudit only: another theme keeps a checkbox');
    }

    public function testACheckboxByDefault(): void
    {
        self::assertFalse($this->factory->create(CheckboxType::class)->createView()->vars['switch']);
        self::assertFalse($this->factory->create(CheckboxType::class, null, ['switch' => false])->createView()->vars['switch']);
    }

    public function testARadioIsNeverASwitch(): void
    {
        self::assertFalse($this->factory->create(RadioType::class, null, ['switch' => true])->createView()->vars['switch']);
    }
}
