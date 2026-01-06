<?php

namespace Lle\CruditBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CKEditorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $event->setData(preg_replace('#</?script>#i', '', (string)$event->getData()));
        });
    }

    public function getParent(): ?string
    {
        return \FOS\CKEditorBundle\Form\Type\CKEditorType::class;
    }
}
