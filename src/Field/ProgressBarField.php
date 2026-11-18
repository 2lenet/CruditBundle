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
            "progressLabel" => null,
            "min" => 0,
            "max" => 100,
            "isBottomLabelActivate" => false,
            "bottomLabel" => null,
            // above max: 'danger' (an alert, as in 1.x: the budget is exceeded), or 'success' (the bar is full, as at max)
            "overMax" => 'danger',
        ]);
        $optionsResolver->setAllowedValues('overMax', ['success', 'danger']);
    }
}
