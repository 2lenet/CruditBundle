<?php

/**
 *  This file is part of the Lego project.
 *
 *   (c) Joris Saenger <joris.saenger@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Lle\CruditBundle\Form\Type;

use Lle\CruditBundle\Service\GedmoTranslatableFieldManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType as ParentType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A field translated with Gedmo: one input per enabled locale, the default locale first, rendered as tabs by the
 * template of CruditUiBundle (form/_translatable.html.twig). A required field must be filled in the default locale
 * (NotBlank, checked by the server); the other locales are optional. fields_options: options given to each input
 * (attr.maxlength, constraints…).
 */
class GedmoTranslatableType extends AbstractType
{
    protected GedmoTranslatableFieldManager $translatablefieldmanager;
    /** @var string[] */
    private array $locales;
    private string $defaultLocale;
    private TranslatorInterface $translator;

    //the 2eme argument is best if $locales
    public function __construct(
        string $defaultLocale,
        array $locales,
        GedmoTranslatableFieldManager $translatableFieldManager,
        TranslatorInterface $translator,
    ) {
        $this->defaultLocale = $defaultLocale;
        $locales = (\count($locales) < 1) ? ['fr', 'en', 'de'] : $locales;
        // The default locale first: its tab is the one shown, its input the one pointed by the label of the field
        $this->locales = array_values(array_unique([$defaultLocale, ...$locales]));
        $this->translatablefieldmanager = $translatableFieldManager;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fieldName = $builder->getName();
        $locales = $this->locales;
        $defaultLocale = $this->defaultLocale;
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($locales, $options, $defaultLocale) {
                $form = $event->getForm();
                foreach ($locales as $locale) {
                    $required = $defaultLocale === $locale && $options['required'];
                    $fieldOptions = $options['fields_options'];
                    $fieldOptions['label'] = false;
                    $fieldOptions['required'] = $required;
                    $fieldOptions['attr'] = ['lang' => str_replace('_', '-', $locale)] + ($fieldOptions['attr'] ?? []);
                    if ($required) {
                        $fieldOptions['constraints'] = [
                            ...(array) ($fieldOptions['constraints'] ?? []),
                            new NotBlank(message: $this->translator->trans(
                                'crudit.gedmo_translatable.required',
                                // As written in a sentence: "français", "English"
                                ['%language%' => \Locale::getDisplayLanguage($locale, $this->translator->getLocale())],
                                'validators'
                            )),
                        ];
                    }
                    $form->add($locale, $options['fields_class'], $fieldOptions);
                }
            }
        );
        // submit
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($locales, $defaultLocale) {
            /** @var FormInterface $form */
            $form = $event->getForm();
            $this->translatablefieldmanager->persistTranslations($form, $locales, $defaultLocale);
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $translatedFieldValues = $this->translatablefieldmanager->getTranslatedFields(
            $form->getParent()?->getData(),
            $form->getName(),
            $this->defaultLocale
        );
        // set form field data (translations)
        foreach ($this->locales as $locale) {
            if (!isset($translatedFieldValues[$locale])) {
                continue;
            }
            if (!$form->isSubmitted()) {
                $form->get($locale)->setData($translatedFieldValues[$locale]);
            }
        }
        // template vars: the locales (the default one first) and the name of each in the language of the page
        $view->vars['locales'] = $this->locales;
        $view->vars['defaultlocale'] = $this->defaultLocale;
        $view->vars['tablabels'] = $this->getTabLabels();
    }

    public function getParent(): string
    {
        return ParentType::class;
    }

    public function getName(): string
    {
        return 'crudit_gedmo_translatable';
    }

    public function getBlockPrefix(): string
    {
        return 'crudit_gedmo_translatable';
    }

    /**
     * @return array<string, string>
     */
    private function getTabLabels(): array
    {
        $tabLabels = [];
        foreach ($this->locales as $locale) {
            $tabLabels[$locale] = $this->languageName($locale);
        }

        return $tabLabels;
    }

    private function languageName(string $locale): string
    {
        $name = \Locale::getDisplayLanguage($locale, $this->translator->getLocale());

        return '' === $name ? $locale : mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => true,
            'mapped' => false,
            'required' => false,
            'by_reference' => false,
            'fields_class' => TextType::class,
            'fields_options' => [],
        ]);
        $resolver->setAllowedTypes('fields_options', 'array');
    }
}
