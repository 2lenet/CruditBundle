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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\FormType as ParentType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Lle\CruditBundle\Service\GedmoTranslatableFieldManager;

class GedmoTranslatableType extends AbstractType
{
    protected GedmoTranslatableFieldManager $translatablefieldmanager;
    private array $locales;
    private string $defaultLocale;
    private string$currentLocale;

    //the 2eme argument is best if $locales
    public function __construct(string $defaultLocale, array $locales, GedmoTranslatableFieldManager $translatableFieldManager, TranslatorInterface $translator)
    {
        $this->defaultLocale = $defaultLocale;
        $this->locales = (\count($locales) <= 1) ? ['fr','en','de'] : $locales;
        $this->translatablefieldmanager = $translatableFieldManager;
        $this->currentLocale = $translator->getLocale();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fieldName = $builder->getName();
        $locales = $this->locales;
        $defaultLocale = $this->defaultLocale;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($locales, $options, $defaultLocale) {
            $form = $event->getForm();
            foreach ($locales as $locale) {
                $form->add($locale, $options['fields_class'], [
                    'label' => false,
                    'required' => ($defaultLocale == $locale && $options['required'])
                ]);
            }
        });
        // submit
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($locales, $defaultLocale) {
            /** @var Form $form */
            $form = $event->getForm();
            $this->translatablefieldmanager->persistTranslations($form, $locales, $defaultLocale);
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $translatedFieldValues = $this->translatablefieldmanager->getTranslatedFields($form->getParent()->getData(), $form->getName(), $this->defaultLocale);
        // set form field data (translations)
        foreach ($this->locales as $locale) {
            if (!isset($translatedFieldValues[$locale])) {
                continue;
            }
            if (!$form->isSubmitted()) {
                $form->get($locale)->setData($translatedFieldValues[$locale]);
            }
        }
        // template vars
        $view->vars['locales'] = $this->locales;
        $view->vars['currentlocale'] = $this->currentLocale;
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

    private function getTabLabels(): array
    {
        $tabLabels = [];
        foreach ($this->locales as $locale) {
            $tabLabels[$locale] = ucfirst(\Locale::getDisplayLanguage($locale, $this->currentLocale));
        }

        return $tabLabels;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => true,
            'mapped' => false,
            'required' => false,
            'by_reference' => false,
            'fields_class' => TextType::class
        ]);
    }
}
