<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Extension;

use Lle\CruditBundle\Form\Extension\SwitchExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * A checkbox shown as a switch (option switch, lot 3.2.b): role="switch" for the theme of CruditUiBundle.
 */
class SwitchExtensionTest extends TypeTestCase
{
    protected function getTypeExtensions(): array
    {
        return [new SwitchExtension()];
    }

    public function testASwitchHasTheRoleSwitchAndKeepsTheAttributesOfTheField(): void
    {
        $view = $this->factory->create(CheckboxType::class, null, ['switch' => true, 'attr' => ['class' => 'mine']])->createView();

        self::assertSame(['role' => 'switch', 'class' => 'mine'], $view->vars['attr']);
    }

    public function testACheckboxByDefault(): void
    {
        self::assertArrayNotHasKey('role', $this->factory->create(CheckboxType::class)->createView()->vars['attr']);
        self::assertArrayNotHasKey('role', $this->factory->create(CheckboxType::class, null, ['switch' => false])->createView()->vars['attr']);
    }

    public function testARadioIsNeverASwitch(): void
    {
        self::assertArrayNotHasKey('role', $this->factory->create(RadioType::class, null, ['switch' => true])->createView()->vars['attr']);
    }
}
