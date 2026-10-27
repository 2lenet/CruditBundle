<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ProgressBarField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ["progress", self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return "@LleCrudit/field/progressbar.html.twig";
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            "barCssClass" => null,
            "progressLabel" => null,
            "progressLabelCssClass" => null,
            "min" => 0,
            "max" => 100,
            "isBottomLabelActivate" => false,
            "bottomLabel" => null,
            // above max: 'success' (the bar is full, as at max), or 'danger' (an alert: the budget is exceeded)
            "overMax" => 'success',
        ]);
        $optionsResolver->setAllowedValues('overMax', ['success', 'danger']);
        foreach (['barCssClass', 'progressLabelCssClass'] as $option) {
            $optionsResolver->setDeprecated(
                $option,
                '2lenet/crudit-bundle',
                '2.0',
                'The option "%name%" has no effect any more: the bar and its value have the look of the design system (option overMax for the tone above max).',
            );
        }
    }
}
