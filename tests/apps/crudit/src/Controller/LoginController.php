<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sign in by hand (http_basic): the application lets anonymous visitors in, so a browser never sends the credentials
 * of its own; this page asks for them (401), then goes to the home page. alice / alice, bob / bob.
 */
class LoginController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(): RedirectResponse
    {
        if ($this->getUser() === null) {
            throw new UnauthorizedHttpException('Basic realm="Gestio"');
        }

        return $this->redirectToRoute('home');
    }
}
