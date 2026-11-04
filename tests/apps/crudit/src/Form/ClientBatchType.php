<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/** The form of a batch action: `ids` and `all_page` are filled by the SelectionBar. */
class ClientBatchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('ids', HiddenType::class);
        $builder->add('all_page', HiddenType::class);
        $builder->add('status', ChoiceType::class, ['choices' => ['Prospect' => 'prospect', 'Customer' => 'customer', 'Lost' => 'lost']]);
    }

    public function getBlockPrefix(): string
    {
        return 'client_batch';
    }
}
