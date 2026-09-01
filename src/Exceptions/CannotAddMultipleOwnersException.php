<?php

declare(strict_types=1);

namespace Vimatech\Membership\Exceptions;

use RuntimeException;

final class CannotAddMultipleOwnersException extends RuntimeException
{
    public function __construct(string $message = 'This entity does not allow multiple owners.')
    {
        parent::__construct($message);
    }
}
