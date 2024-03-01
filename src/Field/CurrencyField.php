<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

class CurrencyField extends AbstractField
{
    public function __construct(
        Environment $twig,
        private RequestStack $requestStack,
        protected ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($twig);
    }

    public function support(string $type): bool
    {
        return (in_array($type, ['decimal', 'currency', self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return '@LleCrudit/field/currency.html.twig';
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        /** @var string $defaultCurrencyAlignment */
        $defaultCurrencyAlignment = $this->parameterBag->get('lle_crudit.default_currency_alignment');

        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            'tableCssClass' => $this->getTableCssClass($defaultCurrencyAlignment),
            'locale' => $this->requestStack->getMainRequest()?->getLocale(),
            'currency' => 'EUR',
            'property' => null,
            'removeHtml' => false,
            'params' => [],
        ]);
    }
}
