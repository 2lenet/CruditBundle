<?php


namespace Lle\CruditBundle\Dto\Action;


use Lle\CruditBundle\Dto\Path;

class DeleteAction extends ItemAction
{
    public const CALLBACK = "canDelete";

    public function getTitle(): string
    {
        $canDelete = true;
        if (method_exists($this->getResource(), static::CALLBACK)) {
            $canDelete = $this->getResource()->{static::CALLBACK}();
        }

        if (is_string($canDelete)) {
            return $canDelete;
        }

        return parent::getLabel();
    }

    public function isDisabled(): bool
    {
        $canDelete = true;
        if (method_exists($this->getResource(), static::CALLBACK)) {
            $canDelete = $this->getResource()->{static::CALLBACK}();
        }

        return $canDelete !== true;
    }
}