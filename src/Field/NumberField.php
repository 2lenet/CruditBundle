<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

class NumberField extends AbstractField
{
    public function __construct(
        Environment $twig,
        protected ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($twig);
    }

    public function support(string $type): bool
    {
        return (in_array($type, ['float', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/number.html.twig';
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            'tableCssClass' => $this->getTableCssClass($this->parameterBag->get('lle_crudit.default_number_alignment')),
            'decimals' => '2',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
        ]);
    }
}
