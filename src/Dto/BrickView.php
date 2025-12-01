<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

use Lle\CruditBundle\Contracts\BrickConfigInterface;

class BrickView
{
    private ?string $template;

    private array $data;

    private array $config = [];

    private bool $movable;

    private string $id;

    protected array $options;

    private ?Path $path;

    private ?string $role = null;

    /** @var callable|null $displayIf */
    protected $displayIf = null;

    public function __construct(BrickConfigInterface $brickConfig, ?string $template = null, array $data = [])
    {
        $this->template = $template;
        $this->data = $data;
        $this->movable = false;
        $this->role = $brickConfig->getRole();
        $this->id = $brickConfig->getId();
        $this->options = $brickConfig->getOptions();
        $this->displayIf = $brickConfig->getDisplayIf();
        $this->setPath(
            $brickConfig->getCrudConfig()->getPath('brickdata', [
                'idBrick' => $brickConfig->getId(),
            ])
        );
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param string $template #Template
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function isMovable(): bool
    {
        return $this->movable;
    }

    public function gid(string $name): string
    {
        return $this->id . '_' . $name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return BrickView
     */
    public function setOptions(array $options): BrickView
    {
        $this->options = $options;

        return $this;
    }

    public function getPath(): ?Path
    {
        return $this->path;
    }

    public function setPath(?Path $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getIndexTemplate(): string
    {
        return $this->template . '/index.html.twig';
    }

    public function getPartial(string $name): string
    {
        return $this->template . '/' . $name . '.html.twig';
    }

    public function getCssClass(): ?string
    {
        if (array_key_exists('cssClass', $this->options)) {
            return $this->options["cssClass"];
        } else {
            return null;
        }
    }

    public function getUrl(array $params): string
    {
        return '';
    }

    public function isDisplayed(mixed $resource = null): bool
    {
        return !$this->displayIf || call_user_func($this->displayIf, $resource);
    }
}
