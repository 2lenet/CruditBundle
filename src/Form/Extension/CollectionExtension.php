<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Collection (lot 3.2.c), rendered by the form theme of CruditUiBundle (controller collection): a list of named lines,
 * the buttons Add (allow_add) and Remove (allow_delete).
 *
 * - `sortable`: the name of the field of the position of a line, a HiddenType of the entry type. The lines get the
 *   buttons Move up and Move down, and the controller writes 1, 2, 3… in this field each time the lines change. The
 *   order is kept only if the entity reads its lines in this order (#[ORM\OrderBy(['position' => 'ASC'])]).
 * - `entry_label`: the text that names a line, with %index% ("Piece %index%"), translated in the domain of the field;
 *   null: "Line %index%" of CruditUiBundle.
 */
class CollectionExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [CollectionType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'sortable' => null,
            'entry_label' => null,
        ]);
        $resolver->setAllowedTypes('sortable', ['null', 'string']);
        $resolver->setAllowedTypes('entry_label', ['null', 'string']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['collection_sortable'] = $options['sortable'];
        $view->vars['collection_entry_label'] = $options['entry_label'];
    }

    /**
     * The field of the position must be a hidden field of each line: checked on the prototype, else on the first line.
     * Checked when the collection is rendered, not when it is built: an empty collection without allow_add (no prototype,
     * no line) is not checked, a wrong option shows only on a page with a line or the button Add.
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $position = $options['sortable'];
        if (null === $position) {
            return;
        }
        $prototype = $form->getConfig()->getAttribute('prototype');
        $entries = $form->all();
        $entry = $prototype instanceof FormInterface ? $prototype : (reset($entries) ?: null);
        if (null === $entry) {
            return;
        }
        if (!$entry->has($position)) {
            throw new InvalidConfigurationException(sprintf('The option "sortable" of the collection "%s" names the field "%s", which its entry type "%s" does not have: add a HiddenType "%s" to it.', $form->getName(), $position, $options['entry_type'], $position));
        }
        if (!$this->isHidden($entry->get($position)->getConfig()->getType())) {
            throw new InvalidConfigurationException(sprintf('The option "sortable" of the collection "%s" names the field "%s", which must be a HiddenType: the position is written by the buttons Move up and Move down.', $form->getName(), $position));
        }
    }

    /**
     * A HiddenType, or a type built on it (its parents).
     */
    private function isHidden(ResolvedFormTypeInterface $type): bool
    {
        while (null !== $type) {
            if ($type->getInnerType() instanceof HiddenType) {
                return true;
            }
            $type = $type->getParent();
        }

        return false;
    }
}
