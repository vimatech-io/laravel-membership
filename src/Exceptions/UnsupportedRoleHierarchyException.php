<?php

declare(strict_types=1);

namespace Vimatech\Membership\Exceptions;

use RuntimeException;

final class UnsupportedRoleHierarchyException extends RuntimeException
{
    public function __construct(string $message = 'Role hierarchy is not supported for plain string roles without configured levels.')
    {
        parent::__construct($message);
    }
}
