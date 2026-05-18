<?php

declare(strict_types=1);

namespace Vimatech\Membership\Support;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Queries\FindMembership;

final class MembershipGate
{
    private Model $member;

    public function __construct(Model $member)
    {
        $this->member = $member;
    }

    public static function for(Model $member): self
    {
        return new self($member);
    }

    public function isMemberOf(Model $membershipable): bool
    {
        return $this->findMembership($membershipable) !== null;
    }

    public function hasRole(Model $membershipable, string|BackedEnum $role): bool
    {
        $membership = $this->findMembership($membershipable);

        if (! $membership) {
            return false;
        }

        $normalized = $role instanceof BackedEnum ? $role->value : $role;

        return $membership->role === $normalized;
    }

    public function isAdmin(Model $membershipable): bool
    {
        $membership = $this->findMembership($membershipable);

        if (! $membership) {
            return false;
        }

        return in_array($membership->role, config('membership.admin_roles', ['owner', 'admin']));
    }

    public function isOwner(Model $membershipable): bool
    {
        $membership = $this->findMembership($membershipable);

        if (! $membership) {
            return false;
        }

        return in_array($membership->role, config('membership.owner_roles', ['owner']));
    }

    public function isAdminOf(Model $membershipable): bool
    {
        return $this->isAdmin($membershipable);
    }

    public function isOwnerOf(Model $membershipable): bool
    {
        return $this->isOwner($membershipable);
    }

    private function findMembership(Model $membershipable): ?Membership
    {
        return app(FindMembership::class)->execute($this->member, $membershipable);
    }
}
