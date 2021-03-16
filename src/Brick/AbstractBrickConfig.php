<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick;

use Lle\CruditBundle\Contracts\BrickConfigInterface;

abstract class AbstractBrickConfig implements BrickConfigInterface
{

    /** @var string  */
    protected $subjectClass = null;

    /** @var string */
    protected $mainSubjectClass = null;

    public function getSubjectClass(): string
    {
        return $this->subjectClass;
    }

    public function setSubjectClass(string $subjectClass): self
    {
        $this->subjectClass = $subjectClass;
        return $this;
    }

    public function getMainSubjectClass(): string
    {
        return $this->mainSubjectClass;
    }

    public function setMainSubjectClass(string $mainSubjectClass): self
    {
        $this->mainSubjectClass = $mainSubjectClass;
        if ($this->subjectClass === null) {
            $this->subjectClass = $mainSubjectClass;
        }
        return $this;
    }

    public function getConfig(): array
    {
        return [];
    }
}
