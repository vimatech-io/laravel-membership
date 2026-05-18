<?php

declare(strict_types=1);

namespace Vimatech\Membership\Exceptions;

use RuntimeException;

final class AlreadyMemberException extends RuntimeException
{
    public function __construct(string $message = 'This member already belongs to this entity.')
    {
        parent::__construct($message);
    }
}
