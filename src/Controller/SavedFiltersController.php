<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Controller;

use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Filter\SavedFilterException;
use Lle\CruditBundle\Filter\SavedFilterManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The sets of filters a user saves for a list (lot 3.1.f). All three are POST with a CSRF token: they change the
 * session or the database, never a link. Each answers with a redirection to the list (`redirect`, an address of the
 * site) and a message. Imported by the project (`@LleCruditBundle/Resources/config/routes.yaml`, UPGRADE-2.0.md).
 *
 * - without a user, or with `lle_crudit.saved_filters.enabled` false: 404 (the feature does not exist);
 * - a set of another user, or one that is gone: "not found" message, the same for both, so that its existence is not told.
 */
class SavedFiltersController
{
    public function __construct(
        private readonly SavedFilterManager $manager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Saves what the form of the filters sent (the panel) under `name`, and applies it as the filters in use.
     */
    #[Route('/crudit/filters/{filterset}/saved', name: 'lle_crudit_saved_filters_save', requirements: ['filterset' => '[^/]+'], methods: ['POST'])]
    public function save(Request $request, string $filterset): Response
    {
        $set = $this->check($request, $filterset);

        try {
            $saved = $this->manager->save($set, $request->request->getString('name'), $request->request->all());
            $this->manager->apply($request, $set, (int) $saved->getId());
            $this->flash($request, 'success', 'crudit.saved_filters.saved', ['%name%' => $saved->getName()]);
        } catch (SavedFilterException $e) {
            $this->failure($request, $e);
        }

        return $this->back($request);
    }

    #[Route('/crudit/filters/{filterset}/saved/{id}/apply', name: 'lle_crudit_saved_filters_apply', requirements: ['filterset' => '[^/]+', 'id' => '\d+'], methods: ['POST'])]
    public function apply(Request $request, string $filterset, int $id): Response
    {
        $set = $this->check($request, $filterset);

        try {
            $saved = $this->manager->apply($request, $set, $id);
            $this->flash($request, 'success', 'crudit.saved_filters.applied', ['%name%' => $saved->getName()]);
        } catch (SavedFilterException $e) {
            $this->failure($request, $e);
        }

        return $this->back($request);
    }

    #[Route('/crudit/filters/{filterset}/saved/{id}/delete', name: 'lle_crudit_saved_filters_delete', requirements: ['filterset' => '[^/]+', 'id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, string $filterset, int $id): Response
    {
        $this->check($request, $filterset);

        try {
            $saved = $this->manager->delete($filterset, $id);
            $this->flash($request, 'success', 'crudit.saved_filters.deleted', ['%name%' => $saved->getName()]);
        } catch (SavedFilterException $e) {
            $this->failure($request, $e);
        }

        return $this->back($request);
    }

    /**
     * @throws NotFoundHttpException
     * @throws AccessDeniedHttpException
     */
    private function check(Request $request, string $filterset): FilterSetInterface
    {
        $set = $this->manager->getFilterSet($filterset);
        if (!$this->manager->isAvailable() || $set === null) {
            throw new NotFoundHttpException();
        }
        if (!$this->manager->isTokenValid($request->request->getString('_token'))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        return $set;
    }

    private function failure(Request $request, SavedFilterException $e): void
    {
        if ($e->reason === SavedFilterException::UNAVAILABLE) {
            throw new NotFoundHttpException(previous: $e);
        }
        $this->flash($request, 'danger', 'crudit.saved_filters.error.' . $e->reason, ['%limit%' => $e->limit]);
    }

    /**
     * @param array<string, scalar> $parameters
     */
    private function flash(Request $request, string $type, string $key, array $parameters = []): void
    {
        // Translated here: the toasts of the layout translate a flash message without parameters.
        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $this->translator->trans($key, $parameters, 'LleCruditBundle'));
        }
    }

    /**
     * Back to the list: `redirect` when it is an address of the site (one slash, no host), else the home page.
     */
    private function back(Request $request): RedirectResponse
    {
        $redirect = $request->request->getString('redirect');
        $local = str_starts_with($redirect, '/') && !str_starts_with($redirect, '//') && !str_contains($redirect, '\\') && !preg_match('/[\x00-\x1f]/', $redirect);

        return new RedirectResponse($local ? $redirect : '/', Response::HTTP_SEE_OTHER);
    }
}
