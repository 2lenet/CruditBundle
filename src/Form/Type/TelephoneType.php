<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Type;

use Lle\CruditBundle\Form\DataTransformer\TelephoneTransformer;
use Lle\CruditBundle\Form\Telephone\CallingCodes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Calling code select + number input, stored as an E.164 string ("+41791234567").
 */
class TelephoneType extends AbstractType
{
    /** Shown instead of a calling code for a legacy value, whose country is unknown */
    public const UNKNOWN_CALLING_CODE = '+ ?';

    /** @var array<string, array<string, string>> country choices by locale */
    private static array $countryChoices = [];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('country', ChoiceType::class, $this->getCountryOptions($options, false))
            ->add('number', TelType::class, [
                'label' => false,
                'required' => $options['required'],
                'attr' => [
                    'class' => 'crudit-telephone-number',
                    'autocomplete' => 'tel-national',
                    'inputmode' => 'tel',
                ],
            ])
            ->addViewTransformer(new TelephoneTransformer($options['default_country']))
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
                $data = $event->getData();
                if (is_string($data) && $data !== '' && TelephoneTransformer::isLegacy($data)) {
                    $event->getForm()->add('country', ChoiceType::class, $this->getCountryOptions($options, true));
                }
            });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $country = $form->get('country')->getData();
        $view->vars['calling_code'] = is_string($country) && CallingCodes::exists($country)
            ? '+' . CallingCodes::getCode($country)
            : self::UNKNOWN_CALLING_CODE;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Otherwise the error is displayed on the parent form instead of under the field
            'error_bubbling' => false,
            'invalid_message' => 'crudit.telephone.invalid',
            'default_country' => 'FR',
            'preferred_countries' => [],
        ]);
        $resolver->setAllowedTypes('default_country', 'string');
        $resolver->setAllowedValues('default_country', CallingCodes::exists(...));
        $resolver->setAllowedTypes('preferred_countries', 'string[]');
        $resolver->setAllowedValues(
            'preferred_countries',
            fn (array $countries): bool => array_all($countries, CallingCodes::exists(...)),
        );
    }

    public function getBlockPrefix(): string
    {
        return 'crudit_telephone';
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function getCountryOptions(array $options, bool $legacy): array
    {
        return [
            'label' => false,
            'choices' => $this->getCountryChoices(),
            'choice_translation_domain' => false,
            'preferred_choices' => $options['preferred_countries'],
            'duplicate_preferred_choices' => false,
            'choice_attr' => fn (string $country): array => ['data-code' => '+' . CallingCodes::getCode($country)],
            'required' => false,
            // "+ ?" only for a legacy value, which has no calling code: offered elsewhere,
            // picking it would just lead to calling_code_required
            'placeholder' => $legacy ? self::UNKNOWN_CALLING_CODE : false,
            'translation_domain' => false,
            // Native select laid transparent over the "+33" box of the widget, TomSelect would replace it
            'disable_tom_select' => true,
            'attr' => [
                'class' => 'crudit-telephone-country',
            ],
        ];
    }

    /**
     * @return array<string, string> "Switzerland (+41)" => "CH", sorted in the current locale
     */
    private function getCountryChoices(): array
    {
        // Built once per locale: the type may be repeated many times in a collection
        $locale = \Locale::getDefault();
        if (isset(self::$countryChoices[$locale])) {
            return self::$countryChoices[$locale];
        }

        $choices = [];
        foreach (CallingCodes::CODES as $country => $code) {
            if (Countries::exists($country)) {
                $choices[sprintf('%s (+%s)', Countries::getName($country), $code)] = $country;
            }
        }

        $collator = new \Collator($locale);
        uksort($choices, fn (string $a, string $b): int => (int)$collator->compare($a, $b));

        return self::$countryChoices[$locale] = $choices;
    }
}
