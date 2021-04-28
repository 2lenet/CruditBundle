<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Exception\CruditException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

abstract class AbstractField implements FieldInterface
{
    /** @var Environment  */
    protected $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /** @param mixed $value */
    public function buildView(FieldView $fieldView, $value): FieldView
    {
        $options = $this->configureOptions($fieldView->getField());
        return $fieldView->setStringValue($this->render($fieldView, $value, $options));
    }

    /** @param mixed $value */
    public function render(FieldView $fieldView, $value, array $options = []): string
    {
        $template = $fieldView->getField()->getTemplate() ?? $this->getDefaultTemplate();
        if ($template) {
            return $this->twig->render($template, [
                'value' => $value,
                'view' => $fieldView,
                'options' => $options,
                'resource' => $fieldView->getResource()
            ]);
        } else {
            throw new CruditException('template field ' . static::class . ' is not define');
        }
    }

    public function configureOptions(Field $field): array
    {
        $optionResolver = new OptionsResolver();
        return $optionResolver->resolve($field->getOptions());
    }

    abstract protected function getDefaultTemplate(): ?string;
}
