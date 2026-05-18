<?php

declare(strict_types=1);

namespace Vimatech\Membership\Exceptions;

use RuntimeException;

final class RoleEscalationException extends RuntimeException
{
    public function __construct(string $message = 'Cannot assign a role higher than your own.')
    {
        parent::__construct($message);
    }
}
