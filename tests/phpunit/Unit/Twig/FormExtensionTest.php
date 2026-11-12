<?php

declare(strict_types=1);

namespace phpunit\Unit\Twig;

use Lle\CruditBundle\Contracts\SanitizerInterface;
use Lle\CruditBundle\Form\Type\GroupType;
use Lle\CruditBundle\Twig\FormExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * The form of a CRUD: sub-forms laid out field by field, the legend of the required fields, the deprecation of the
 * Bootstrap column classes, the autofill kept away from the inputs typed in, the format helpers of the 1.x.
 */
class FormExtensionTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [new PreloadedExtension([new GroupType($this->createStub(SanitizerInterface::class))], [])];
    }

    public function testASubFormIsLaidOutFieldByField(): void
    {
        $view = $this->form(fn (FormBuilderInterface $form) => $form
            ->add('name', TextType::class)
            ->add($form->create('address', FormType::class)->add('city', TextType::class))
            ->add('empty', FormType::class)
            ->add('channels', ChoiceType::class, ['choices' => ['a' => 'a'], 'expanded' => true])
            ->add('since', DateType::class, ['widget' => 'choice']));

        self::assertFalse(FormExtension::isFlattened($view['name']));
        self::assertTrue(FormExtension::isFlattened($view['address']));
        self::assertFalse(FormExtension::isFlattened($view['empty']), 'no field');
        self::assertFalse(FormExtension::isFlattened($view['channels']), 'a widget of its own');
        self::assertFalse(FormExtension::isFlattened($view['since']), 'a widget of its own');
    }

    public function testTheLegendForARequiredField(): void
    {
        self::assertTrue(FormExtension::hasRequired($this->form(fn (FormBuilderInterface $form) => $form->add('name', TextType::class))));
        self::assertFalse(FormExtension::hasRequired($this->form(fn (FormBuilderInterface $form) => $form->add('name', TextType::class, ['required' => false]))));
    }

    public function testSectionsHiddenFieldsAndTheTokenAreNotFields(): void
    {
        self::assertFalse(FormExtension::hasRequired($this->form(fn (FormBuilderInterface $form) => $form
            ->add('identity', GroupType::class, ['label' => 'Identity', 'mapped' => false])
            ->add('id', HiddenType::class)
            ->add('_token', HiddenType::class)
            ->add('name', TextType::class, ['required' => false]))));
    }

    public function testTheLegendForARequiredFieldOfASubForm(): void
    {
        self::assertTrue(FormExtension::hasRequired($this->form(fn (FormBuilderInterface $form) => $form
            ->add('name', TextType::class, ['required' => false])
            ->add($form->create('address', FormType::class)->add('city', TextType::class)))));
    }

    public function testNoLegendForASubFormWhoseFieldsAreAllOptional(): void
    {
        // The sub-form is required by default, but it is laid out field by field: no field with a star
        self::assertFalse(FormExtension::hasRequired($this->form(fn (FormBuilderInterface $form) => $form
            ->add('name', TextType::class, ['required' => false])
            ->add($form->create('address', FormType::class)->add('city', TextType::class, ['required' => false])))));
    }

    public function testACompoundFieldOfASubFormIsShownAsItIs(): void
    {
        // address is laid out field by field, its own sub-form "geo" is one field (form_row): required by default
        self::assertTrue(FormExtension::hasRequired($this->form(fn (FormBuilderInterface $form) => $form
            ->add($form->create('address', FormType::class)
                ->add('city', TextType::class, ['required' => false])
                ->add($form->create('geo', FormType::class)->add('lat', TextType::class, ['required' => false]))))));
    }

    public function testABootstrapColumnClassIsToldByADeprecation(): void
    {
        self::assertSame(
            ['Since 2lenet/crudit-bundle 2.0: The class "form-control col-md-6" of the field "name" no longer sets its width in a Crudit form: use the option "span" (1, 2, 3 or "full").'],
            $this->deprecations('form-control col-md-6'),
        );
        foreach (['col', 'col-6', 'col-auto', 'col-md', 'col-md-6', 'col-xxl-auto', 'mine col-lg'] as $class) {
            self::assertCount(1, $this->deprecations($class), $class);
        }
    }

    public function testAnotherClassIsNotADeprecation(): void
    {
        self::assertSame([], $this->deprecations('collapse crudit-upper-format column-x col-form-label'));
        self::assertSame([], $this->deprecations(null));
    }

    public function testTheAutofillIsKeptAwayFromTheInputsTypedInOnly(): void
    {
        $view = $this->form(fn (FormBuilderInterface $form) => $form
            ->add('name', TextType::class, ['attr' => ['class' => 'mine']])
            ->add('email', EmailType::class, ['attr' => ['autocomplete' => 'email']])
            ->add('notes', TextareaType::class)
            ->add('quantity', IntegerType::class)
            ->add('price', MoneyType::class)
            ->add('active', CheckboxType::class)
            ->add('kind', ChoiceType::class, ['choices' => ['a' => 'a']])
            ->add('since', DateType::class, ['widget' => 'choice'])
            ->add('tint', ColorType::class)
            ->add('secret', HiddenType::class));

        foreach (['notes', 'quantity', 'price'] as $name) {
            self::assertSame('new-password', FormExtension::fieldAttr($view[$name])['autocomplete'] ?? null, $name);
        }
        self::assertSame(['class' => 'mine', 'autocomplete' => 'new-password'], FormExtension::fieldAttr($view['name']));
        self::assertSame('email', FormExtension::fieldAttr($view['email'])['autocomplete'], 'the purpose given by the field');
        foreach (['active', 'kind', 'since', 'tint', 'secret'] as $name) {
            self::assertArrayNotHasKey('autocomplete', FormExtension::fieldAttr($view[$name]), $name);
        }
    }

    public function testTheFormatHelpersOfThe1xHaveTheirController(): void
    {
        self::assertSame(['class' => 'mine'], FormExtension::formatAttr(['class' => 'mine']));
        self::assertSame(
            ['class' => 'crudit-upper-format', 'data-controller' => '2lenet--crudit-bundle--input-format'],
            FormExtension::formatAttr(['class' => 'crudit-upper-format']),
        );
        self::assertSame(
            ['class' => 'x crudit-email-format', 'data-controller' => 'mine 2lenet--crudit-bundle--input-format', 'spellcheck' => 'false', 'inputmode' => 'email'],
            FormExtension::formatAttr(['class' => 'x crudit-email-format', 'data-controller' => 'mine']),
        );
        self::assertSame(
            ['class' => 'crudit-ip-format', 'inputmode' => 'numeric', 'data-controller' => '2lenet--crudit-bundle--input-format', 'spellcheck' => 'false'],
            FormExtension::formatAttr(['class' => 'crudit-ip-format', 'inputmode' => 'numeric']),
            'the keyboard given by the field is kept',
        );
        self::assertArrayNotHasKey('inputmode', FormExtension::formatAttr(['class' => 'crudit-ip-format']), 'the keyboard decimal of iOS has the comma of the language, not the point');
    }

    private function form(callable $build): FormView
    {
        $builder = $this->factory->createBuilder(FormType::class);
        $build($builder);

        return $builder->getForm()->createView();
    }

    /**
     * @return list<string> the deprecations triggered for a field with this class
     */
    private function deprecations(?string $class): array
    {
        $view = $this->factory->createNamed('name', TextType::class, null, $class === null ? [] : ['attr' => ['class' => $class]])->createView();
        $messages = [];
        set_error_handler(static function (int $level, string $message) use (&$messages): bool {
            $messages[] = $message;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            FormExtension::fieldDeprecations($view);
        } finally {
            restore_error_handler();
        }

        return $messages;
    }
}
