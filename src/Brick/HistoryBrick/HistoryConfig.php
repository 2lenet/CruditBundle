<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\HistoryBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;

class HistoryConfig extends AbstractBrickConfig
{
    private string $logEntryClassName;

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function getLogEntryClassName(): ?string
    {
        return $this->logEntryClassName;
    }

    public function setLogEntryClassName(string $className): self
    {
        $this->logEntryClassName = $className;

        return $this;
    }
}
