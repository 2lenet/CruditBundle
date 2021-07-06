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
 * FormType that uses autocomplete for entities.
 */
class AutocompleteType extends AbstractType
{
    public const DEFAULT_AUTOCOMPLETE_ROUTE = "app_crudit_%s_autocomplete";

    private RouterInterface $router;

    private EntityManagerInterface $em;

    public function __construct(
        RouterInterface $router,
        EntityManagerInterface $em
    ) {
        $this->router = $router;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($class = $options["class"]) {
            $transformer = new EntityToIdTransformer($this->em);
            $transformer->setClass($class);
            $transformer->setMultiple($options["multiple"]);
            $builder->addModelTransformer($transformer);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $classFqcn = $options["class"];

        // Gestion URL autocomplete
        if ($route = $options["route"]) {
            $url = $this->router->generate($route);
        } elseif ($options["url"]) {
            $url = $options["url"];
        } else {
            if (!$classFqcn) {
                throw new CruditException(sprintf(
                    "You must set the 'class' option in %s",
                    self::class
                ));
            }

            $class = strtolower(str_replace("App\\Entity\\", "", $classFqcn));
            $url = $this->router->generate(
                sprintf(self::DEFAULT_AUTOCOMPLETE_ROUTE, $class)
            );
        }

        $view->vars["url"] = $url;

        // Gestion valeur déjà existante
        if ($id = $view->vars["value"]) {
            if ($options["multiple"]) {
                $entities = $this->em
                    ->getRepository($classFqcn)
                    ->findBy(["id" => explode(",", $id)]);
            } else {
                $entities = [$this->em
                    ->getRepository($classFqcn)
                    ->find($id)];
            }

            $items = [];
            foreach ($entities as $entity) {
                $items[] = [
                    "id" => $entity->getId(),
                    "text" => (string)$entity,
                ];
            }

            $view->vars["items"] = json_encode($items);
        }

        // Gestion multi valeurs
        $view->vars["multiple"] = $options["multiple"];
    }

    public function configureOptions(OptionsResolver $resolver)
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
