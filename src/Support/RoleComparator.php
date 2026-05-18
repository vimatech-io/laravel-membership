<?php

declare(strict_types=1);

namespace Vimatech\Membership\Support;

use BackedEnum;
use Vimatech\Membership\Contracts\MembershipRole;
use Vimatech\Membership\Exceptions\UnsupportedRoleHierarchyException;

final class RoleComparator
{
    public function normalize(string|BackedEnum $role): string
    {
        return $role instanceof BackedEnum ? $role->value : $role;
    }

    public function level(string|BackedEnum $role): ?int
    {
        if ($role instanceof MembershipRole) {
            return $role->level();
        }

        $normalized = $this->normalize($role);
        $configRoles = config('membership.roles', []);

        return $configRoles[$normalized] ?? null;
    }

    public function isAtLeast(string|BackedEnum $actual, string|BackedEnum $required): bool
    {
        $actualLevel = $this->level($actual);
        $requiredLevel = $this->level($required);

        if ($actualLevel === null || $requiredLevel === null) {
            throw new UnsupportedRoleHierarchyException;
        }

        return $actualLevel >= $requiredLevel;
    }
}
