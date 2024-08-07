<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

class DeleteAction extends ItemAction
{
    public const CALLBACK = "canDelete";

    public function __construct(string $label)
    {
        parent::__construct($label);
    }

    public function getTitle(): string
    {
        if ($this->getResource() !== null) {
            $canDelete = true;
            if (method_exists($this->getResource(), static::CALLBACK)) {
                $canDelete = $this->getResource()->{static::CALLBACK}();
            }

            if (is_string($canDelete)) {
                return $canDelete;
            }
        }

        return parent::getLabel();
    }

    public function isDisabled(): bool
    {
        if ($this->getResource() !== null) {
            $canDelete = true;
            if (method_exists($this->getResource(), static::CALLBACK)) {
                $canDelete = $this->getResource()->{static::CALLBACK}();
            }

            return $canDelete !== true;
        }

        return false;
    }
}
