<?php

declare(strict_types=1);

namespace App\Form;

use Lle\CruditBundle\Form\Type\CKEditorType;
use Lle\CruditBundle\Form\Type\GroupType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;

/**
 * The form to edit a client: the one of ClientType, under a title (ClientCrudConfig), plus a section in the
 * translation domain "client_form" with the widgets of other bundles in the bricks (lle_crudit.form_themes: the editor
 * of FOSCKEditorBundle, a file of VichUploaderBundle) and a sub-form laid out field by field whose own error (a
 * constraint on the object, error_bubbling: false) is shown. On the edit page only: the editor would load its script in the smoke test.
 */
class ClientEditType extends ClientType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('more', GroupType::class, [
                'label' => 'more.title',
                'description' => 'more.description',
                'columns' => 2,
                'mapped' => false,
                'translation_domain' => 'client_form',
            ])
            ->add('presentation', CKEditorType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'presentation.label',
                'tooltip' => 'presentation.tooltip',
                'translation_domain' => 'client_form',
            ])
            ->add('attachment', VichFileType::class, [
                'mapped' => false,
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
            ])
            ->add($builder->create('address', FormType::class, [
                'mapped' => false,
                'required' => false,
                // Its errors stay on it (a compound field sends them to the form by default, shown above the form)
                'error_bubbling' => false,
                'constraints' => [new Callback(static function (mixed $address, ExecutionContextInterface $context): void {
                    if (\is_array($address) && ($address['city'] ?? null) !== null && ($address['postcode'] ?? null) === null) {
                        $context->addViolation('Enter the postcode of the city.');
                    }
                })],
            ])
                ->add('city', TextType::class, ['required' => false])
                ->add('postcode', TextType::class, ['required' => false]));
    }

    public function getBlockPrefix(): string
    {
        return 'client';
    }
}
