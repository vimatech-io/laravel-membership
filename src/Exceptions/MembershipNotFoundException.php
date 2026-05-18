<?php

declare(strict_types=1);

namespace Vimatech\Membership\Exceptions;

use RuntimeException;

final class MembershipNotFoundException extends RuntimeException
{
    public function __construct(string $message = 'Membership not found.')
    {
        parent::__construct($message);
    }
}
