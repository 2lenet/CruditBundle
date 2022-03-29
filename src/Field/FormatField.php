<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Dto\FieldView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormatField extends AbstractField
{
    public function support(string $type): bool
    {
        return (in_array($type, ['format', 'twig', self::class]));
    }

    /** @param mixed $value */
    public function buildView(FieldView $fieldView, $value): FieldView
    {
        $optionResolver = new OptionsResolver();
        $this->configureOptions($optionResolver);
        $options = $optionResolver->resolve($fieldView->getField()->getOptions());
        $template = $this->twig->createTemplate($options['format']);
        return $fieldView->setStringValue($template->render([
            'value' => $value,
            'view' => $fieldView,
            'resource' => $fieldView->getResource(),
            'options' => $options
        ]));
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver
            ->setRequired([
            'format'
        ])->setAllowedTypes('format', 'string');
    }

    public function getDefaultTemplate(): ?string
    {
        return null;
    }
}
