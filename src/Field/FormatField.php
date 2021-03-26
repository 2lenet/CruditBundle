<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Field;


use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Twig\Environment;

class FormatField implements FieldInterface
{
    /** @var Environment  */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function support(string $type): bool
    {
        return (in_array($type, ['format', 'twig', self::class]));
    }

    /** @param mixed $value */
    public function buildView(Field $field, $value): FieldView
    {
        $template = $this->twig->createTemplate($field->getOptions()['format']);
        return new FieldView(
            $field,
            $value,
            $template->render(['value' => $value])
        );
    }

}
