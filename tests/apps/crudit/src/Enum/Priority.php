<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * The priority of a client: a backed enum shown by a ChoiceField (lot 3.4.a).
 */
enum Priority: string
{
    case Low = 'low';
    case High = 'high';
}
