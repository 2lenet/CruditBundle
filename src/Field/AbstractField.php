<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;

use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Exception\CruditException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

abstract class AbstractField implements FieldInterface
{
    public const ALIGN_LEFT = 'left';

    public const ALIGN_CENTER = 'center';

    public const ALIGN_RIGHT = 'right';

    protected Environment $twig;

    protected ParameterBagInterface $parameterBag;

    public function __construct(Environment $twig, ParameterBagInterface $parameterBag)
    {
        $this->twig = $twig;
        $this->parameterBag = $parameterBag;
    }

    /** @param mixed $value */
    public function buildView(FieldView $fieldView, $value): FieldView
    {
        $optionResolver = new OptionsResolver();
        $this->configureOptions($optionResolver);
        $options = $optionResolver->resolve($fieldView->getField()->getOptions());
        $fieldView->setOptions($options);

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
                'resource' => $fieldView->getResource(),
            ]);
        } else {
            throw new CruditException('Template field ' . static::class . ' is not defined');
        }
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            "cssClass" => $this->parameterBag->get('lle_crudit.css_class'),
            "tableCssClass" => "text-nowrap",
            "edit_route" => null,
            "sortProperty" => null,
            'editRole' => null,
        ]);
    }

    public function getTableCssClass(string $defaultAlignment): string
    {
        $alignment = 'text-end';

        switch ($defaultAlignment) {
            case self::ALIGN_LEFT:
                $alignment = 'text-start';
                break;
            case self::ALIGN_CENTER:
                $alignment = 'text-center';
                break;
            case self::ALIGN_RIGHT:
            default:
                $alignment = 'text-end';
                break;
        }

        return $alignment;
    }

    abstract protected function getDefaultTemplate(): ?string;
}
