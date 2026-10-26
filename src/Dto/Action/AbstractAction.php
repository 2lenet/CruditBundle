<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Contracts\ActionInterface;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditUiBundle\Component\ButtonVariant;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractAction implements ActionInterface
{
    protected string $label;

    protected Path $path;

    protected ?Icon $icon;

    protected ?string $url = null;

    protected ?string $cssClass = null;

    protected bool $hideLabel = false;

    protected ?string $modal = null;

    protected bool $confirmModal = false;

    protected array $config = [];

    protected ?string $target = null;

    protected ?string $role = null;

    protected ?bool $hideIfDisabled = null;

    protected bool $dropdown = false;

    protected ?string $template = null;

    protected string $variant = 'secondary';

    protected bool $primary = false;

    protected ?string $group = null;

    protected ?string $translationDomain = null;

    /** @var callable|string|null $disabledReason */
    protected $disabledReason = null;

    /** @var callable|null $displayIf */
    protected $displayIf = null;

    /** @var callable|null $displayIfByRequest */
    protected $displayIfByRequest = null;

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    public function getId(): string
    {
        return md5('crudit_action_' . spl_object_id($this));
    }

    /**
     * The reason when the action is disabled, else its label.
     */
    public function getTitle(): string
    {
        return $this->getDisabledReason() ?? $this->getLabel();
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPath(): Path
    {
        return $this->path;
    }

    public function setPath(Path $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * The role of the path (deprecated Path::setRole()), null without path (a dropdown).
     */
    public function getPathRole(): ?string
    {
        return isset($this->path) ? $this->path->getRole() : null;
    }

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function setIcon(?Icon $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    /**
     * Classes added to the element of the action. The Bootstrap classes (btn, btn-*) have no effect since 2.0: the
     * look comes from setVariant().
     */
    public function setCssClass(?string $cssClass): static
    {
        $classes = preg_split('/\s+/', trim((string) $cssClass), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $kept = array_filter($classes, static fn (string $class): bool => preg_match('/^btn(?:-|$)/', $class) !== 1);
        if (count($kept) !== count($classes)) {
            trigger_deprecation(
                '2lenet/crudit-bundle',
                '2.0',
                'The Bootstrap classes "%s" of the action "%s" have no effect, use setVariant().',
                implode(' ', array_diff($classes, $kept)),
                $this->label,
            );
        }
        $this->cssClass = $kept ? implode(' ', $kept) : null;

        return $this;
    }

    public function getVariant(): string
    {
        return $this->variant;
    }

    /**
     * The look of the action: a variant of the Button of CruditUiBundle (ButtonVariant), checked here since the
     * component does not validate its props.
     */
    public function setVariant(string|ButtonVariant $variant): static
    {
        if (is_string($variant) && ButtonVariant::tryFrom($variant) === null) {
            throw new \InvalidArgumentException(sprintf(
                'The variant "%s" of the action "%s" is not one of "%s".',
                $variant,
                $this->label,
                implode('", "', ButtonVariant::values()),
            ));
        }
        $this->variant = $variant instanceof ButtonVariant ? $variant->value : $variant;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    /**
     * The primary action of the page (a single one: the first wins, the others are secondary).
     */
    public function setPrimary(bool $primary = true): static
    {
        $this->primary = $primary;

        return $this;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->translationDomain;
    }

    /**
     * The domain of the translation of the label, null: the one of the CRUD (the actions of the bundle: its own).
     */
    public function setTranslationDomain(?string $translationDomain): static
    {
        $this->translationDomain = $translationDomain;

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * The heading of the action in the menu "More" (setDropdown(true)).
     */
    public function setGroup(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Why the action cannot be used (a rule of the business, not a right): it stays shown, disabled, with this reason.
     * Null when it can be used.
     */
    public function getDisabledReason(): ?string
    {
        if (is_callable($this->disabledReason)) {
            return call_user_func($this->disabledReason, $this->getReasonSubject()) ?: null;
        }

        return $this->disabledReason ?: null;
    }

    /**
     * @param callable|string|null $disabledReason a reason (a key of translation), or a callable given the resource
     *                                             that returns the reason or null
     */
    public function setDisabledReason(callable|string|null $disabledReason): static
    {
        $this->disabledReason = $disabledReason;

        return $this;
    }

    /**
     * What the callable of setDisabledReason() is given: the resource of an item action.
     */
    protected function getReasonSubject(): mixed
    {
        return null;
    }

    public function isHideLabel(): bool
    {
        return $this->hideLabel;
    }

    public function setHideLabel(bool $hideLabel): static
    {
        $this->hideLabel = $hideLabel;

        return $this;
    }

    public function getModal(): ?string
    {
        return $this->modal;
    }

    /**
     * @param ?string $modal #Template
     */
    public function setModal(?string $modal): static
    {
        $this->modal = $modal;

        return $this;
    }

    public function getConfirmModal(): bool
    {
        return $this->confirmModal;
    }

    public function setConfirmModal(bool $confirmModal): static
    {
        $this->confirmModal = $confirmModal;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(string $target): static
    {
        $this->target = $target;

        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->getDisabledReason() !== null;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getHideIfDisabled(): ?bool
    {
        return $this->hideIfDisabled;
    }

    public function setHideIfDisabled(?bool $hideIfDisabled): static
    {
        $this->hideIfDisabled = $hideIfDisabled;

        return $this;
    }

    public function isDropdown(): bool
    {
        return $this->dropdown;
    }

    public function setDropdown(bool $dropdown): self
    {
        $this->dropdown = $dropdown;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function isDisplayed(mixed $resource = null): bool
    {
        return !$this->displayIf || call_user_func($this->displayIf, $resource);
    }

    public function isDisplayedByRequest(?Request $request = null): bool
    {
        return !$this->displayIfByRequest || call_user_func($this->displayIfByRequest, $request);
    }

    public function setDisplayIf(?callable $displayIf = null): self
    {
        $this->displayIf = $displayIf;

        return $this;
    }

    public function setDisplayIfByRequest(?callable $displayIfByRequest): self
    {
        $this->displayIfByRequest = $displayIfByRequest;

        return $this;
    }
}
