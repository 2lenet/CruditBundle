<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait AdresseTrait
{
    /**
     * @ORM\Column("string", length=255, nullable=true)
     */
    private ?string $adresse1;

    /**
     * @ORM\Column("string", length=255, nullable=true)
     */
    private ?string $adresse2;

    /**
     * @ORM\Column("string", length=20, nullable=true)
     */
    private ?string $cp;

    /**
     * @ORM\Column("string", length=255, nullable=true)
     */
    private ?string $ville;

    /**
     * @ORM\Column("string", length=20, nullable=true)
     */
    private ?string $codeInsee;

    /**
     * @ORM\Column("string", length=20, nullable=true)
     */
    private ?string $tel;

    /**
     * @ORM\Column("string", length=255, nullable=true)
     */
    private ?string $email;

    public function getAdresseComplete()
    {
        return $this->adresse1 . " " . $this->cp . " " . $this->ville;
    }

    public function getAdresse1(): ?string
    {
        return $this->adresse1;
    }

    public function setAdresse1(?string $adresse1): self
    {
        $this->adresse1 = $adresse1;

        return $this;
    }

    public function getAdresse2(): ?string
    {
        return $this->adresse2;
    }

    public function setAdresse2(?string $adresse2): self
    {
        $this->adresse2 = $adresse2;

        return $this;
    }

    public function getCp(): ?string
    {
        return $this->cp;
    }

    public function setCp(?string $cp): self
    {
        $this->cp = $cp;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCodeInsee(): ?string
    {
        return $this->codeInsee;
    }

    public function setCodeInsee(?string $codeInsee): self
    {
        $this->codeInsee = $codeInsee;

        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }
    public function setTel(?string $tel): self
    {
        $this->tel = $tel;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
