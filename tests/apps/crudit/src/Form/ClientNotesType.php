<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The notes of a client, edited in a tab of its record (lot 3.3.b): saved, the form comes back to its tab.
 */
class ClientNotesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('notes', TextareaType::class, ['label' => 'field.notes', 'required' => false]);
    }
}
