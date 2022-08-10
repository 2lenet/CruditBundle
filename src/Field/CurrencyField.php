<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

class CurrencyField extends AbstractField
{
    private RequestStack $requestStack;

    public function __construct(Environment $twig, RequestStack $requestStack)
    {
        parent::__construct($twig);
        $this->requestStack = $requestStack;
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
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            "tableCssClass" => "text-end",
            "locale" => $this->requestStack->getMainRequest()->getLocale(),
            "currency" => "EUR",
            "property" => null,
            "removeHtml" => false,
            "params" => [],
        ]);
    }
}
