<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Short choices with one answer shown as a segmented control (lot 3.2.b): option `segmented` of a choice, with
 * `expanded` and without `multiple`. The radios of CruditUiBundle (crudit-ui-segmented) instead of a list of radios.
 */
class SegmentedExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('segmented', false);
        $resolver->setAllowedTypes('segmented', 'bool');
        $resolver->setNormalizer('segmented', static function (Options $options, bool $segmented): bool {
            if ($segmented && (!$options['expanded'] || $options['multiple'])) {
                throw new InvalidOptionsException('The option "segmented" needs a choice with "expanded" and without "multiple": short choices, one answer.');
            }

            return $segmented;
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['segmented'] = $options['segmented'];
    }
}
