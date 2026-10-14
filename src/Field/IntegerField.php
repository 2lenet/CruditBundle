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
        parent::__construct($twig, $this->parameterBag);
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
        /** @var string $defaultIntegerAlignment */
        $defaultIntegerAlignment = $this->parameterBag->get('lle_crudit.default_integer_alignment');

        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            'tableCssClass' => $this->getTableCssClass($defaultIntegerAlignment),
            // null: separator of the locale of the request (1 234 in fr, 1,234 in en)
            'thousands_separator' => null,
        ]);
    }
}
