<?= "<?php" ?>

namespace <?= $namespace ?>;

use Lle\CruditBundle\Contracts\MenuProviderInterface;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Layout\AbstractLayoutElement;
use Lle\CruditBundle\Dto\Layout\HorizontalSeparatorElement;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Layout\ExternalLinkElement;
use Lle\CruditBundle\Dto\Path;

class <?= $className ?> implements MenuProviderInterface
{
    public function getMenuEntry(): array
    {
        return [
<?php foreach ($items as $item) { ?>
<?php if (isset($item["type"]) && $item["type"] === "separator") { ?>
        
                HorizontalSeparatorElement::new()<?php if (isset($item["role"])) { ?>->setRole("<?= $item["role"] ?>")<?php } ?><?php if (isset($item["parent"])) { ?>->setParent("<?= str_replace(".", "-", $item["parent"]) ?>")<?php } ?>,
        
<?php } elseif (isset($item["url"])) { ?>
                ExternalLinkElement::new("<?= $item["label"] ?>", "<?= $item["url"] ?>")<?php if (isset($item["icon"])) { ?>->setIcon(Icon::new("<?= $item["icon"] ?>")) <?php } ?><?php if (isset($item["role"])) { ?>->setRole("<?= $item["role"] ?>")<?php } ?><?php if (isset($item["parent"])) { ?>->setParent("<?= str_replace(".", "-", $item["parent"]) ?>")<?php } ?>,
<?php } else { ?>
                LinkElement::new("<?= $item["label"] ?>", <?php if (isset($item["route"])) { ?>Path::new("<?= $item["route"] ?>")<?php } else { ?>null<?php } ?>)->setId("<?= str_replace(".", "-", $item["label"]) ?>")<?php if (isset($item["icon"])) { ?>->setIcon(Icon::new("<?= $item["icon"] ?>"))<?php } ?><?php if (isset($item["role"])) { ?>->setRole("<?= $item["role"] ?>")<?php } ?><?php if (isset($item["parent"])) { ?>->setParent("<?= str_replace(".", "-", $item["parent"]) ?>")<?php } ?>,
<?php } ?>
<?php } ?>
        ];
    }
}
