<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

class TemplateElement extends AbstractLayoutElement
{
    /** @var string */
    private $template;

    public static function new(string $template): self
    {
        return new self($template);
    }

    public function __construct(string $template)
    {
        $this->template = $template;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
