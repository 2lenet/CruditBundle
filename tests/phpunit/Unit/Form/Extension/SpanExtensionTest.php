<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Extension;

use Lle\CruditBundle\Contracts\SanitizerInterface;
use Lle\CruditBundle\Form\Extension\SpanExtension;
use Lle\CruditBundle\Form\Type\GroupType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * Width of a field in a Crudit form (option span) and section options (GroupType: description, columns).
 */
class SpanExtensionTest extends TypeTestCase
{
    protected function getTypeExtensions(): array
    {
        return [new SpanExtension()];
    }

    protected function getExtensions(): array
    {
        return [new PreloadedExtension([new GroupType($this->createStub(SanitizerInterface::class))], [])];
    }

    public function testASpanIsAClassOfTheRow(): void
    {
        $form = $this->factory->createBuilder()
            ->add('name', TextType::class)
            ->add('designation', TextType::class, ['span' => 2, 'row_attr' => ['class' => 'mine']])
            ->add('address', TextType::class, ['span' => 3])
            ->add('notes', TextType::class, ['span' => 'full'])
            ->getForm()
            ->createView();

        self::assertArrayNotHasKey('class', $form['name']->vars['row_attr']);
        self::assertSame('mine crudit-form-field--span-2', $form['designation']->vars['row_attr']['class']);
        self::assertSame('crudit-form-field--span-3', $form['address']->vars['row_attr']['class']);
        self::assertSame('crudit-form-field--span-full', $form['notes']->vars['row_attr']['class']);
    }

    public function testAnUnknownSpanIsRefused(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory->create(TextType::class, null, ['span' => 4]);
    }

    public function testABootstrapColumnClassIsNotADeprecationOutsideTheFormsOfCrudit(): void
    {
        $messages = $this->deprecations(fn () => $this->factory->createNamed('name', TextType::class, null, ['attr' => ['class' => 'form-control col-md-6']])->createView());

        self::assertSame([], $messages, 'told by crudit_form_field_deprecations() when Crudit shows the form, not for a form of the project');
    }

    public function testASectionHasADescriptionAndItsColumns(): void
    {
        $view = $this->factory->createNamed('address', GroupType::class, null, ['label' => 'Address', 'description' => 'Where we go', 'columns' => 2, 'mapped' => false])->createView();

        self::assertTrue($view->vars['isGroup']);
        self::assertSame('Where we go', $view->vars['description']);
        self::assertSame(2, $view->vars['columns']);
        self::assertNull($this->factory->create(GroupType::class)->createView()->vars['columns']);
    }

    public function testASectionHasOneToFourColumns(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory->create(GroupType::class, null, ['columns' => 5]);
    }

    /**
     * @return list<string> the deprecations triggered by $run
     */
    private function deprecations(callable $run): array
    {
        $messages = [];
        set_error_handler(static function (int $level, string $message) use (&$messages): bool {
            $messages[] = $message;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $run();
        } finally {
            restore_error_handler();
        }

        return $messages;
    }
}
