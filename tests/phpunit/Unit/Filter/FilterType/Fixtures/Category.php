<?php

namespace phpunit\Unit\Filter\FilterType\Fixtures;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity without translations.
 */
#[ORM\Entity]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public ?string $name = null;
}
