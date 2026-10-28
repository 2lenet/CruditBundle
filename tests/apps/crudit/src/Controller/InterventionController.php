<?php

declare(strict_types=1);

namespace App\Controller;

use App\Crudit\Config\InterventionCrudConfig;
use Lle\CruditBundle\Controller\AbstractCrudController;
use Lle\CruditBundle\Controller\TraitCrudController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/intervention')]
class InterventionController extends AbstractCrudController
{
    use TraitCrudController;

    public function __construct(InterventionCrudConfig $config)
    {
        $this->config = $config;
    }

    /**
     * The number of interventions in each state (crudit_workflow_counters, Counters of CruditUiBundle).
     */
    #[Route('/counters', name: 'app_intervention_counters')]
    public function counters(): Response
    {
        return $this->render('intervention/counters.html.twig', ['crud' => $this->config]);
    }
}
