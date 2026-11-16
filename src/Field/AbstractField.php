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
            // Columns of the grid of a section of the record taken by the field (1 to 3, or the whole line), null:
            // one, or the whole line for a long text (isWide())
            'span' => null,
            "edit_route" => null,
            "sortProperty" => null,
            'editRole' => null,
            // the alignment of its column in a list (start, end), null: the one of the type (getAlignment())
            'alignment' => null,
        ]);
        $optionsResolver->setAllowedValues('alignment', [null, 'start', 'end']);
        $optionsResolver->setAllowedValues('span', [null, 1, 2, 3, 'full']);
    }

    /**
     * A long text (several lines, rich text, JSON): the whole line of a section of the record, unless the option span
     * of the field says otherwise.
     */
    public function isWide(): bool
    {
        return false;
    }

    /**
     * Alignment of the value in a column of a list: start (the text), end (the numbers, see the configuration
     * lle_crudit.default_*_alignment). Logical, so that it follows the direction of the language.
     */
    public function getAlignment(): string
    {
        return 'start';
    }

    /**
     * The alignment of the configuration (left, center, right) as a logical one: right gives end, the others start
     * (a column of the table is aligned at its start or at its end).
     */
    protected function logicalAlignment(string $alignment): string
    {
        return $alignment === self::ALIGN_RIGHT ? 'end' : 'start';
    }

    abstract protected function getDefaultTemplate(): ?string;
}
