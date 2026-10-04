<?php

declare(strict_types=1);

namespace Vimatech\Membership\Exceptions;

use RuntimeException;

final class CannotTransferOwnershipException extends RuntimeException
{
    public static function toCurrentOwner(): self
    {
        return new self('Ownership cannot be transferred to the member who already holds it.');
    }

    public static function withoutOwnerRole(): self
    {
        return new self('Ownership cannot be transferred: membership.owner_roles is empty. Configure the role that marks an owner.');
    }

    public static function withoutDemotionRole(): self
    {
        return new self('Ownership cannot be transferred: membership.admin_roles has no role outside membership.owner_roles to demote the previous owner to. Add one, for example "admin".');
    }
}
