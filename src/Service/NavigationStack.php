<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class NavigationStack
{
    public const SESSION_KEY = 'lle_crudit_referers';
    private const MAX_SIZE = 20;

    public function __construct(
        protected RequestStack $requestStack,
    ) {
    }

    public function push(string $url): void
    {
        $stack = $this->all();

        if (!empty($stack) && end($stack) === $url) {
            return;
        }

        $stack[] = $url;

        while (count($stack) > self::MAX_SIZE) {
            array_shift($stack);
        }

        $this->save($stack);
    }

    /**
     * Removes the last entry if it matches $url (back-navigation scenario).
     * Returns true if an entry was removed.
     */
    public function removeIfLast(string $url): bool
    {
        $stack = $this->all();

        if (!empty($stack) && end($stack) === $url) {
            array_pop($stack);
            $this->save($stack);

            return true;
        }

        return false;
    }

    public function peek(): ?string
    {
        $stack = $this->all();

        return !empty($stack) ? end($stack) : null;
    }

    public function all(): array
    {
        $request = $this->requestStack->getMainRequest();
        if ($request === null || !$request->hasSession()) {
            return [];
        }

        return json_decode($request->getSession()->get(self::SESSION_KEY, '[]'), true) ?? [];
    }

    private function save(array $stack): void
    {
        $request = $this->requestStack->getMainRequest();
        if ($request === null || !$request->hasSession()) {
            return;
        }

        $request->getSession()->set(self::SESSION_KEY, json_encode($stack));
    }
}
