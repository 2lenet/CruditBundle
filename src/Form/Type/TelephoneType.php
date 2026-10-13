<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Type;

use Lle\CruditBundle\Form\DataTransformer\TelephoneTransformer;
use Lle\CruditBundle\Form\Telephone\CallingCodes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Calling code select + number input, stored as an E.164 string ("+41791234567").
 */
class TelephoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('country', ChoiceType::class, [
                'label' => false,
                'choices' => $this->getCountryChoices(),
                'choice_translation_domain' => false,
                'preferred_choices' => $options['preferred_countries'],
                'duplicate_preferred_choices' => false,
                'attr' => [
                    'class' => 'crudit-telephone-country',
                ],
            ])
            ->add('number', TelType::class, [
                'label' => false,
                'required' => $options['required'],
                'attr' => [
                    'class' => 'crudit-telephone-number',
                    'autocomplete' => 'tel-national',
                    'inputmode' => 'tel',
                ],
            ])
            ->addViewTransformer(new TelephoneTransformer($options['default_country']));
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
    }

    public function getBlockPrefix(): string
    {
        return 'crudit_telephone';
    }

    /**
     * @return array<string, string> "Switzerland (+41)" => "CH", sorted in the current locale
     */
    private function getCountryChoices(): array
    {
        $choices = [];
        foreach (CallingCodes::CODES as $country => $code) {
            if (Countries::exists($country)) {
                $choices[sprintf('%s (+%s)', Countries::getName($country), $code)] = $country;
            }
        }

        $collator = new \Collator(\Locale::getDefault());
        uksort($choices, fn (string $a, string $b): int => (int)$collator->compare($a, $b));

        return $choices;
    }
}
