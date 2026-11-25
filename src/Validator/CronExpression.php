<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * A cron expression ("30 8 * * 1,4", or a nickname such as "@daily"), checked by dragonmantank/cron-expression. An empty value passes:
 * add NotBlank to require one. Added by CronExpressionType; an entity can also use it on its property.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class CronExpression extends Constraint
{
    public const string INVALID_ERROR = '6f0a2d4e-8c1b-4a7e-9d3f-2b5c8e1a4f70';

    protected const array ERROR_NAMES = [
        self::INVALID_ERROR => 'INVALID_ERROR',
    ];

    public string $message = 'crudit.cron.invalid';

    /**
     * @param string[]|null $groups
     */
    public function __construct(?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
        $this->message = $message ?? $this->message;
    }
}
