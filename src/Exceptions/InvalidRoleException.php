<?php

declare(strict_types=1);

namespace Vimatech\Membership\Exceptions;

use RuntimeException;

final class InvalidRoleException extends RuntimeException
{
    public function __construct(string $role)
    {
        parent::__construct("Invalid role: {$role}.");
    }
}
