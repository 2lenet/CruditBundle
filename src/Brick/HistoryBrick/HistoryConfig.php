<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\HistoryBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;

class HistoryConfig extends AbstractBrickConfig
{
    /**
     * @var class-string<object>|null
     */
    private ?string $logEntryClassName = null;

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /** @return class-string<object>|null */
    public function getLogEntryClassName(): ?string
    {
        return $this->logEntryClassName;
    }
    
    /**
     * @param class-string<object>|null $className
     * @return $this
     */
    public function setLogEntryClassName(string $className): self
    {
        $this->logEntryClassName = $className;

        return $this;
    }
}
