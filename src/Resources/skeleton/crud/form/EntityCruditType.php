<?= "<?php" ?>
<?php if ($strictType): ?>

declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace ?>;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class <?= $prefixFilename ?>Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
<?php foreach ($fields as $field) { ?>
        $builder->add("<?= $field->getName() ?>");
<?php } ?>
    }

    public function getName(): string
    {
        return "<?= strtolower($prefixFilename) ?>_form";
    }
}
