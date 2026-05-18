<?php

declare(strict_types=1);

namespace Vimatech\Membership\Tests\Fixtures;

use Vimatech\Membership\Contracts\MembershipRole;

enum OrganizationRole: string implements MembershipRole
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function level(): int
    {
        return match ($this) {
            self::Owner => 100,
            self::Admin => 50,
            self::Member => 10,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
        };
    }
}
