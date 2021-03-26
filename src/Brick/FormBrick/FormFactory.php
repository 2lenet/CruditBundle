<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\FormBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\DataSourceInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Resolver\RessourceResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class FormFactory extends AbstractBasicBrickFactory
{
    /** @var FormFactoryInterface */
    private $formFactory;

    public function __construct(
        RessourceResolver $ressourceResolver,
        RequestStack $requestStack,
        FormFactoryInterface $formFactory
    ) {
        parent::__construct($ressourceResolver, $requestStack);
        $this->formFactory = $formFactory;
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return get_class($brickConfigurator) === FormConfig::class;
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        /** @var FormConfig $brickConfigurator */
        $ressource = $this->getRessource($brickConfigurator->getDataSource());
        $form = $this->generateForm($brickConfigurator, $ressource);
        $this->bindRequest($form, $brickConfigurator, $ressource);
        /** @var FormConfig $brickConfigurator */
        $view = new BrickView(spl_object_hash($brickConfigurator));
        $view
            ->setTemplate('@LleCrudit/brick/form')
            ->setConfig($brickConfigurator->getConfig())
            ->setData(['form' => $form->createView()]);
        return $view;
    }

    private function bindRequest(Form $form, FormConfig $brickConfig, object $ressource)
    {
        $form->handleRequest($this->getRequest());
        if ($this->getRequest()->getMethod() === 'POST' and $form->isSubmitted()) {
            if ($form->isValid()) {
                $brickConfig->getDataSource()->save($ressource);
                dd($brickConfig->getCrudConfig()->getPath());
            } else {
                //gest error
            }
        }
    }

    private function generateForm(FormConfig $brickConfigurator, object $ressource): Form
    {
        if(!$brickConfigurator->getForm()){
            $formBuilder = $this->formFactory->createBuilder(FormType::class, $ressource, ['allow_extra_fields'=>true]);
            foreach ($brickConfigurator->getFields() as $field) {
               $formBuilder->add($field->getName(), $field->getType(), $field->getOptions());
            }
            return $formBuilder->getForm();
        }else {
            return $this->formFactory->create($brickConfigurator->getForm(), $ressource);
        }
    }

    private function getRessource(DataSourceInterface $datasource): object
    {
        if($this->getRequest()->get('id')) {
            return $datasource->get($this->getRequest()->get('id'));
        } else {
            return $datasource->newInstance();
        }
    }

}
