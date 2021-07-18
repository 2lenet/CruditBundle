<?= "<?php" ?>
<?php if ($strictType): ?>


declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace ?>;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class <?= $entityClass ?>Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
<?php foreach($fields as $field): ?>
        $builder->add('<?= $field ?>');
<?php endforeach ?>
    }

    public function getName()
    {
        return '<?= strtolower($entityClass) ?>_form';
    }
}
