<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

class NumberField extends AbstractField
{
    public function __construct(
        Environment $twig,
        protected ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($twig, $this->parameterBag);
    }

    public function support(string $type): bool
    {
        return (in_array($type, ['float', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/number.html.twig';
    }

    public function getAlignment(): string
    {
        /** @var string $alignment */
        $alignment = $this->parameterBag->get('lle_crudit.default_number_alignment');

        return $this->logicalAlignment($alignment);
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            'decimals' => 2,
            // null: separators of the locale of the request (1 234,56 in fr, 1,234.56 in en)
            'decimal_separator' => null,
            'thousands_separator' => null,
        ]);
        // an integer for NumberFormatter, also when a project gives a string ('3')
        $optionsResolver->setAllowedTypes('decimals', ['int', 'string']);
        $optionsResolver->setNormalizer('decimals', static fn (Options $options, int|string $decimals): int => (int)$decimals);
    }
}
