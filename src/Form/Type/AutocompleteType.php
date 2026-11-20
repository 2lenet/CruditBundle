<?php

namespace Lle\CruditBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

/**
 * An entity chosen in a list with search (lot 3.2.c): the choices are asked to the route autocomplete of its CRUD
 * (`GET <url>?q=&offset=&limit=`, TraitCrudController::autocomplete()), 20 at a time. The value is the id, or the ids
 * joined by a comma with `multiple`.
 */
class AutocompleteType extends AbstractType
{
    public const DEFAULT_AUTOCOMPLETE_ROUTE = "app_crudit_%s_autocomplete";
    private RouterInterface $router;
    private EntityManagerInterface $em;

    public function __construct(
        RouterInterface $router,
        EntityManagerInterface $em,
    ) {
        $this->router = $router;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($class = $options["class"]) {
            $transformer = new EntityToIdTransformer($this->em);
            $transformer->setClass($class);
            $transformer->setMultiple($options["multiple"]);
            $builder->addModelTransformer($transformer);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $classFqcn = $options["class"];

        // URL of the route that gives the choices
        if ($route = $options["route"]) {
            $url = $this->router->generate($route);
        } elseif ($options["url"]) {
            $url = $options["url"];
        } else {
            if (!$classFqcn) {
                throw new CruditException(
                    sprintf(
                        "You must set the 'class' option in %s",
                        self::class
                    )
                );
            }

            $class = strtolower(str_replace("App\\Entity\\", "", $classFqcn));
            $url = $this->router->generate(
                sprintf(self::DEFAULT_AUTOCOMPLETE_ROUTE, $class)
            );
        }

        $view->vars["url"] = $url;

        // The choices made: their label, in the order of the value
        $selected = [];
        $value = $view->vars["value"];
        if (is_string($value) && $value !== '' && $classFqcn) {
            $ids = $options["multiple"] ? array_values(array_unique(explode(",", $value))) : [$value];
            /** @var class-string $classFqcn */
            $repository = $this->em->getRepository($classFqcn);
            $entities = $options["multiple"] ? $repository->findBy(["id" => $ids]) : array_filter([$repository->find($value)]);
            $labels = [];
            /** @var object $entity */
            foreach ($entities as $entity) {
                // getId() is already needed by EntityToIdTransformer to give the value
                if (\method_exists($entity, 'getId')) {
                    // an entity without __toString() is shown with its id, but stays chosen: the hidden input of the
                    // list is made from the choices shown, an entity left out would be removed when saving the form
                    $id = (string) $entity->getId();
                    $labels[$id] = $entity instanceof \Stringable ? (string) $entity : $id;
                }
            }
            // an id without entity (hidden by a Doctrine filter, deleted) stays chosen, shown with the id: the form then
            // refuses it (EntityToIdTransformer), instead of removing it from the relation without a message
            foreach ($ids as $id) {
                $selected[] = ["value" => $id, "label" => $labels[$id] ?? $id];
            }
        }

        $view->vars["multiple"] = $options["multiple"];
        // The list with search of CruditUiBundle (block crudit_ui_combobox_widget), its choices asked to the route
        $view->vars["combobox"] = true;
        $view->vars["combobox_url"] = $url;
        $view->vars["combobox_selected"] = $selected;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "class" => null,
            "url" => null,
            "route" => null,
            "multiple" => false,
            // do not remove this option
            "compound" => false,
        ]);

        $resolver
            ->setAllowedTypes("class", ["string", "null"])
            ->setRequired("class")
            ->setAllowedTypes("url", ["string", "null"])
            ->setAllowedTypes("route", ["string", "null"])
            ->setAllowedTypes("multiple", "bool");
    }
}
