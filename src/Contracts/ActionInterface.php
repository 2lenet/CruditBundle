<?php

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditUiBundle\Component\ButtonVariant;

interface ActionInterface
{
    public function __construct(string $label);

    public function getId(): string;

    public function getTitle(): string;

    public function getLabel(): string;

    public function getPath(): Path;

    public function getIcon(): ?Icon;

    public function setIcon(?Icon $icon): static;

    public function getUrl(): ?string;

    public function setUrl(string $url): static;

    public function getCssClass(): ?string;

    public function setCssClass(?string $cssClass): static;

    public function isHideLabel(): bool;

    public function setHideLabel(bool $hideLabel): static;

    public function getModal(): ?string;

    public function setModal(?string $modal): static;

    public function getConfirmModal(): bool;

    public function setConfirmModal(bool $confirmModal): static;

    public function getConfig(): array;

    public function setConfig(array $config): static;

    public function getTarget(): ?string;

    public function setTarget(string $target): static;

    public function isDisabled(): bool;

    public function getRole(): ?string;

    public function setRole(string $role): static;

    public function getHideIfDisabled(): ?bool;

    public function setHideIfDisabled(?bool $hideIfDisabled): static;

    public function getVariant(): string;

    public function setVariant(string|ButtonVariant $variant): static;

    public function isPrimary(): bool;

    public function setPrimary(bool $primary = true): static;

    public function getTranslationDomain(): ?string;

    public function setTranslationDomain(?string $translationDomain): static;

    public function isBack(): bool;

    public function setBack(bool $back = true): static;

    public function getGroup(): ?string;

    public function setGroup(?string $group): static;

    public function getDisabledReason(): ?string;

    public function setDisabledReason(callable|string|null $disabledReason): static;

    public function getTemplate(): ?string;

    /**
     * @param ?string $template #Template
     */
    public function setTemplate(?string $template): static;
}
