<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;


use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

class TemplateField implements FieldInterface
{
    /** @var Environment  */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function support(string $type): bool
    {
        return (in_array($type, ['template', self::class]));
    }

    /** @param mixed $value */
    public function buildView(Field $field, $value): FieldView
    {
        $options = $this->configureOptions($field);
        return new FieldView(
            $field,
            $value,
            $this->twig->render($options['template'], ['value' => $value])
        );
    }

    public function configureOptions(Field $field)
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setRequired([
            'template'
        ])->setAllowedTypes('template', 'string');
        return $optionResolver->resolve($field->getOptions());
    }

}
