<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

class IntegerField extends AbstractField
{
    public function __construct(
        Environment $twig,
        protected ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($twig);
    }

    public function support(string $type): bool
    {
        return (in_array($type, ['integer', 'smallint', 'bigint', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/integer.html.twig';
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            'tableCssClass' => $this->getTableCssClass($this->parameterBag->get('lle_crudit.default_integer_alignment')),
            'thousands_separator' => ' ',
        ]);
    }
}
