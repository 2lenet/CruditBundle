<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TabBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\BrickConfigInterface;

/**
 * The tabs of a record, under its sections: the tabs component of CruditUiBundle (ARIA pattern, the tab in the address,
 * ?tab=<id>). Each tab has an id, given from its label when not set ("tab.history" gives "history"), unique in the
 * brick.
 */
class TabConfig extends AbstractBrickConfig
{
    /** @var Tab[] */
    private array $tabs = [];

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options)
    {
        foreach ($options['tabs'] ?? [] as $tab) {
            $this->addTab($tab);
        }
    }

    /** @return Tab[] */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    public function add(
        string $label,
        BrickConfigInterface $brickConfig,
        ?string $role = null,
        ?callable $displayIf = null,
        ?callable $displayIfByRequest = null,
        ?string $id = null,
        ?callable $count = null,
    ): self {
        return $this->adds($label, [$brickConfig], $role, $displayIf, $displayIfByRequest, $id, $count);
    }

    /** @param BrickConfigInterface[] $bricksConfig */
    public function adds(
        string $label,
        array $bricksConfig,
        ?string $role = null,
        ?callable $displayIf = null,
        ?callable $displayIfByRequest = null,
        ?string $id = null,
        ?callable $count = null,
    ): self {
        return $this->addTab(
            Tab::new($label, $bricksConfig)
                ->setRole($role)
                ->setDisplayIf($displayIf)
                ->setDisplayIfByRequest($displayIfByRequest)
                ->setId($id)
                ->setCount($count)
        );
    }

    public function addTab(Tab $tab): self
    {
        $tab->setId($this->uniqueId($tab->getId() ?? self::idOf($tab->getLabel())));
        $this->tabs[] = $tab;

        return $this;
    }

    /**
     * The id of a label: without "tab.", in lower case ASCII, any other character than a letter, a digit, _ or -
     * replaced by _ ("tab.history" gives "history", "Pièces jointes" gives "pieces_jointes").
     */
    public static function idOf(string $label): string
    {
        $label = preg_replace('/^tab\./', '', $label) ?? $label;
        $ascii = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()')?->transliterate($label);
        $id = trim((string) preg_replace('/[^a-z0-9_-]+/', '_', \is_string($ascii) ? $ascii : strtolower($label)), '_');

        return '' === $id ? 'tab' : $id;
    }

    private function uniqueId(string $id): string
    {
        $taken = array_map(static fn (Tab $tab): ?string => $tab->getId(), $this->tabs);
        $unique = $id;
        for ($i = 2; \in_array($unique, $taken, true); ++$i) {
            $unique = $id . '_' . $i;
        }

        return $unique;
    }

    public function getChildren(): array
    {
        $brickConfigs = [];
        foreach ($this->tabs as $tab) {
            foreach ($tab->getBricks() as $brick) {
                $brickConfigs[] = $brick;
                foreach ($brick->getChildren() as $childBrick) {
                    $brickConfigs[] = $childBrick;
                }
            }
        }

        return $brickConfigs;
    }
}
