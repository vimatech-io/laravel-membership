<?php

declare(strict_types=1);

namespace Vimatech\Membership\Exceptions;

use RuntimeException;

final class CannotRemoveLastAdminException extends RuntimeException
{
    public function __construct(string $message = 'Cannot remove the last admin of this entity.')
    {
        parent::__construct($message);
    }
}
