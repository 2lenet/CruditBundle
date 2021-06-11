<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\FilterBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Dto\Filter\Filter;
use Symfony\Component\HttpFoundation\Request;

class FilterConfig extends AbstractBrickConfig
{

    private ?FilterSetInterface $filterset = null;
    private string $className;

    public function setCrudConfig(CrudConfigInterface $crudConfig): self
    {
        parent::setCrudConfig($crudConfig);
        return $this;
    }

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function getConfig(Request $request): array
    {
        return [
            'filterset' => $this->getFilterset(),
            'name' => $this->getCrudConfig()->getName(),
            'title' => $this->getCrudConfig()->getTitle('list')
        ];
    }

    public function setFilterset(?FilterSetInterface $filterset): self
    {
        $this->filterset = $filterset;
        return $this;
    }

    public function setClassName(string $className): self
    {
        $this->className = $className;
        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function getFilterset(): ?FilterSetInterface
    {
        return $this->filterset;
    }
}
