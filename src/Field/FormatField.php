<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Dto\Field\Field;
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
        $options = $this->configureOptions($fieldView->getField());
        $template = $this->twig->createTemplate($options['format']);
        return $fieldView->setStringValue($template->render([
            'value' => $value,
            'view' => $fieldView,
            'resource' => $fieldView->getResource(),
            'options' => $options
        ]));
    }

    public function configureOptions(Field $field): array
    {
        $optionResolver = new OptionsResolver();
        $optionResolver
            ->setRequired([
            'format'
        ])->setAllowedTypes('format', 'string');
        return $optionResolver->resolve($field->getOptions());
    }

    public function getDefaultTemplate(): ?string
    {
        return null;
    }
}
