<?php

declare(strict_types=1);

namespace App\Form;

use Lle\CruditBundle\Form\Type\GroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The form of a client (lot 3.2.a): a section on the 4 columns of the form, then one of 2 columns with a description;
 * a required field with a help and a tooltip, widths (span 2, 3, full; 3 in the section of 2), a switch, choices shown
 * as boxes, a unit; a field with its own autocomplete and a tooltip_html to clean.
 */
class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('identity', GroupType::class, ['label' => 'Identity', 'mapped' => false])
            ->add('name', null, [
                'help' => 'As on the invoice',
                'empty_data' => '',
                'tooltip' => 'The name of the company, or of the person',
                'constraints' => [new NotBlank(message: 'Enter the name of the client.')],
            ])
            ->add('email', null, [
                'span' => 2,
                'required' => false,
                'attr' => ['autocomplete' => 'email'], // its own autocomplete, not the new-password of the form
                'tooltip_html' => '<strong>Work</strong> address<script>alert(1)</script><em onclick="alert(1)">only</em>',
            ])
            ->add('progress', PercentType::class, ['type' => 'integer', 'required' => false, 'span' => 3])
            ->add('contact', GroupType::class, ['label' => 'Contact', 'description' => 'How to reach the client', 'columns' => 2, 'mapped' => false])
            ->add('active', CheckboxType::class, ['required' => false, 'attr' => ['role' => 'switch']])
            ->add('channels', ChoiceType::class, [
                'choices' => ['client.channel.email' => 'email', 'client.channel.phone' => 'phone'],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'span' => 3, // wider than its section of 2 columns: the whole line
            ])
            ->add('notes', TextareaType::class, ['required' => false, 'span' => 'full']);
    }
}
