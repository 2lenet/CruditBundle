<?php

namespace phpunit\Unit\Filter\FilterType\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Entity with its own Gedmo translation table.
 */
#[ORM\Entity]
#[Gedmo\TranslationEntity(class: ProductTranslation::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    #[Gedmo\Translatable]
    public ?string $label = null;

    #[ORM\Column]
    public ?string $code = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    public ?Category $category = null;

    /** @var Collection<int, ProductTranslation> */
    #[ORM\OneToMany(mappedBy: 'object', targetEntity: ProductTranslation::class)]
    public Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }
}
