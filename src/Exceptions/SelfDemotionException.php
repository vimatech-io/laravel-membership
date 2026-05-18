<?php

declare(strict_types=1);

namespace Vimatech\Membership\Exceptions;

use RuntimeException;

final class SelfDemotionException extends RuntimeException
{
    public function __construct(string $message = 'Cannot demote yourself.')
    {
        parent::__construct($message);
    }
}
