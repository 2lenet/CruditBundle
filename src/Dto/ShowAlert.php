<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

/**
 * A message above a record (CrudConfigInterface::getShowAlert(), lot 3.3.a), rendered by the Alert of CruditUiBundle:
 * an icon and a text, never the colour alone (getCssClass() of the 1.x). The message is a key of the translation
 * domain of the CRUD, given its parameters.
 *
 *     return $resource->isLate() ? ShowAlert::warning('alert.late', ['%days%' => $resource->getDelay()]) : null;
 */
final class ShowAlert
{
    public const TONES = ['info', 'warning', 'danger'];

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private readonly string $tone,
        private readonly string $message,
        private readonly array $parameters = [],
    ) {
        if (!\in_array($tone, self::TONES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'The tone of a ShowAlert is one of %s, "%s" given.',
                implode(', ', self::TONES),
                $tone,
            ));
        }
    }

    /** @param array<string, mixed> $parameters */
    public static function info(string $message, array $parameters = []): self
    {
        return new self('info', $message, $parameters);
    }

    /** @param array<string, mixed> $parameters */
    public static function warning(string $message, array $parameters = []): self
    {
        return new self('warning', $message, $parameters);
    }

    /** @param array<string, mixed> $parameters */
    public static function danger(string $message, array $parameters = []): self
    {
        return new self('danger', $message, $parameters);
    }

    public function getTone(): string
    {
        return $this->tone;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /** @return array<string, mixed> */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
