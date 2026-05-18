<?php

declare(strict_types=1);

namespace Vimatech\Membership\Exceptions;

use RuntimeException;

final class CannotRemoveLastOwnerException extends RuntimeException
{
    public function __construct(string $message = 'Cannot remove the last owner of this entity.')
    {
        parent::__construct($message);
    }
}
