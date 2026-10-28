<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

/**
 * State of an element in a workflow (WorkflowStates): a Status of CruditUiBundle in the tone of the place (metadata
 * `tone`), and, if the user may apply transitions, a menu of the states they lead to, confirmed in a dialog.
 *
 * Options: name (the workflow, else the first one of the element), backgroundColor (deprecated, no effect: declare a
 * tone per place).
 */
class WorkflowField extends AbstractField
{
    private ?string $name;

    public function __construct(Environment $twig, ParameterBagInterface $parameterBag, ?string $name = null)
    {
        parent::__construct($twig, $parameterBag);
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
            "backgroundColor" => null,
        ]);
        $optionsResolver->setAllowedTypes('name', ['null', 'string']);
        $optionsResolver->setAllowedTypes('backgroundColor', ['null', 'string']);
        $optionsResolver->setDeprecated(
            'backgroundColor',
            '2lenet/crudit-bundle',
            '2.0',
            'The option "%name%" has no effect any more: declare a tone per state in the metadata of the places of the workflow (tone: info).',
        );
    }
}
