<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Empty pages of the menu (routes in config/routes/pages.yaml): the name of a route has no underscore, as the menu
 * compares the routes up to their last underscore to find the current entry.
 */
class PageController extends AbstractController
{
    public function page(string $slug): Response
    {
        return $this->render('page.html.twig', ['slug' => $slug]);
    }

    /**
     * A page that overrides the blocks which were in included templates in 1.x (logo, side_header, side_footer,
     * header_nav, header_right) and the block footer.
     */
    #[Route('/overrides', name: 'overrides')]
    public function overrides(): Response
    {
        return $this->render('overrides.html.twig');
    }
}
