<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ListBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfigurator;
use Lle\CruditBundle\Dto\Field\Field;

class ListItem extends AbstractBrickConfigurator
{

    /** @var Field[] */
    private $fields = [];

    /** @var array  */
    private $options;

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function add(Field $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    public function addAuto(array $columns): self
    {
        foreach ($columns as $column) {
            $this->add(Field::new($column));
        }
        return $this;
    }

    /** @return Field[] */
    public function getFields(): array
    {
        return $this->fields;
    }
}
