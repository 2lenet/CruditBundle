<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface BrickConfigInterface
{

    public function getConfig(): array;

    public function getSubjectClass(): string;

    public function getMainSubjectClass(): string;

    public function setMainSubjectClass(string $mainClass): self;
}
