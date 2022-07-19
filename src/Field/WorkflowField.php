<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

class WorkflowField extends AbstractField
{
    private ?string $name;

    public function __construct(Environment $twig, $name = null)
    {
        parent::__construct($twig);
        $this->name = $name;
    }

    public function support(string $type): bool
    {
        return (in_array($type, ["workflow", self::class]));
    }

    public function getDefaultTemplate(): ?string
    {
        return "@LleCrudit/field/workflow.html.twig";
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            "name" => $this->name,
        ]);
    }
}
