<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A contact of a client (ClientEditType, lot 3.2.c): a line of a collection with an order, its position in a hidden
 * field (option sortable).
 *
 * @extends AbstractType<array<string, mixed>>
 */
class ContactLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'contacts.name', 'constraints' => [new NotBlank()]])
            ->add('phone', TextType::class, ['label' => 'contacts.phone', 'required' => false])
            ->add('position', HiddenType::class);
    }

    public function getBlockPrefix(): string
    {
        return 'contact_line';
    }
}
