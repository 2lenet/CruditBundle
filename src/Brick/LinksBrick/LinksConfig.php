<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\LinksBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Dto\Action\BackAction;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Path;

class LinksConfig extends AbstractBrickConfig
{
    
    public const NEW = 'new';
    
    protected $actions = [];
    
    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }
    
    public function addAction(ListAction $action): self
    {
        $this->actions[] = $action;
        return $this;
    }
    
    public function addBack(): self
    {
        //todo rework
        $this->addAction(BackAction::new('crudit.back',new Path('back')));
        return $this;
    }
    
    public function add(string $actionName): self
    {
        if ($actionName === static::NEW) {
            $this->addAction(new ListAction('crudit.add', ));
        }
        return $this;
    }

    /** @return ListAction[] */
    public function getActions(): array
    {
        return $this->actions;
    }
}
