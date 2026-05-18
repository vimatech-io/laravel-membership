<?php

declare(strict_types=1);

namespace Vimatech\Membership\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Queries\FindMembership;
use Vimatech\Membership\Support\RoleComparator;

/**
 * @mixin Model
 */
trait HasMemberships
{
    public function memberships(): MorphMany
    {
        $model = config('membership.models.membership', Membership::class);

        return $this->morphMany($model, 'member');
    }

    public function isMemberOf(Model $membershipable): bool
    {
        return app(FindMembership::class)->execute($this, $membershipable) !== null;
    }

    public function membershipFor(Model $membershipable): ?Membership
    {
        return app(FindMembership::class)->execute($this, $membershipable);
    }

    public function hasRole(Model $membershipable, string|BackedEnum $role): bool
    {
        $membership = app(FindMembership::class)->execute($this, $membershipable);

        if (! $membership) {
            return false;
        }

        $normalized = $role instanceof BackedEnum ? $role->value : $role;

        return $membership->role === $normalized;
    }

    public function hasAnyRole(Model $membershipable, array $roles): bool
    {
        $membership = app(FindMembership::class)->execute($this, $membershipable);

        if (! $membership) {
            return false;
        }

        $normalized = array_map(
            fn (string|BackedEnum $role) => $role instanceof BackedEnum ? $role->value : $role,
            $roles
        );

        return in_array($membership->role, $normalized);
    }

    public function hasRoleAtLeast(Model $membershipable, string|BackedEnum $role): bool
    {
        $membership = app(FindMembership::class)->execute($this, $membershipable);

        if (! $membership) {
            return false;
        }

        return app(RoleComparator::class)->isAtLeast($membership->role, $role);
    }

    public function membershipables(?string $type = null): Collection
    {
        $query = $this->memberships()->with('membershipable');

        if ($type) {
            $query->where('membershipable_type', $type);
        }

        return $query->get()->pluck('membershipable');
    }

    public function ownedMemberships(): MorphMany
    {
        return $this->memberships()->owners();
    }

    public function adminMemberships(): MorphMany
    {
        return $this->memberships()->admins();
    }
}
