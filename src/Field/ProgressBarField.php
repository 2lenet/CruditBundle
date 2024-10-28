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
            "theme" => null,
            "progressValue" => null,
            "progressLabelCssClass" => null,
            "min" => 0,
            "max" => 100,
            "isBottomLabelActivate" => false,
            "isNotPercentBottomLabel"=> false,
            "isStripped" => false,
        ]);
    }
}