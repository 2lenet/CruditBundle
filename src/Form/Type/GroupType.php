<?php

namespace Lle\CruditBundle\Form\Type;

use Lle\CruditBundle\Contracts\SanitizerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A section of a Crudit form: the fields added after it, up to the next section, in a fieldset named by its label
 * (`<legend>` with a `<h2>`). Options: `description` (a text under the title), `columns` (most fields on a line of
 * this section, 1 to 4; null: those of the form). A section takes the whole line, the option `span` is refused.
 */
class GroupType extends AbstractType
{
    protected SanitizerInterface $sanitizer;

    public function __construct(SanitizerInterface $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['description' => null, 'columns' => null]);
        $resolver->setAllowedTypes('description', ['null', 'string']);
        $resolver->setAllowedValues('columns', [null, 1, 2, 3, 4]);
        // A section has no width (SpanExtension): the number of fields on its lines is the option columns
        $resolver->setDefault('span', null);
        $resolver->setAllowedValues('span', [null]);
        $resolver->setInfo('span', 'A section takes the whole line: set the number of fields on its lines with the option "columns"');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
        $view->vars['isGroup'] = true;
        $view->vars['description'] = $options['description'];
        $view->vars['columns'] = $options['columns'];
    }

    public function getName(): string
    {
        return 'crudit_group';
    }
}
