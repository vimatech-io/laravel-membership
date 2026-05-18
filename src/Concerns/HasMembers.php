<?php

declare(strict_types=1);

namespace Vimatech\Membership\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Vimatech\Membership\Actions\AddMember;
use Vimatech\Membership\Actions\RemoveMember;
use Vimatech\Membership\Actions\UpdateMemberRole;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Queries\FindMembership;

/**
 * @mixin Model
 */
trait HasMembers
{
    public function memberships(): MorphMany
    {
        $model = config('membership.models.membership', Membership::class);

        return $this->morphMany($model, 'membershipable');
    }

    public function members(): Collection
    {
        return $this->memberships()->with('member')->get()->pluck('member');
    }

    public function addMember(
        Model $member,
        string|BackedEnum $role,
        ?Model $invitedBy = null,
        array $metadata = [],
        ?Model $actor = null,
    ): Membership {
        return app(AddMember::class)->execute(
            membershipable: $this,
            member: $member,
            role: $role,
            invitedBy: $invitedBy,
            metadata: $metadata,
            actor: $actor,
        );
    }

    public function removeMember(Model $member, ?Model $actor = null): bool
    {
        return app(RemoveMember::class)->execute(
            membershipable: $this,
            member: $member,
            actor: $actor,
        );
    }

    public function updateMemberRole(
        Model $member,
        string|BackedEnum $role,
        ?Model $actor = null,
    ): Membership {
        return app(UpdateMemberRole::class)->execute(
            membershipable: $this,
            member: $member,
            role: $role,
            actor: $actor,
        );
    }

    public function hasMember(Model $member): bool
    {
        return app(FindMembership::class)->execute($member, $this) !== null;
    }

    public function hasMemberWithRole(Model $member, string|BackedEnum $role): bool
    {
        $membership = app(FindMembership::class)->execute($member, $this);

        if (! $membership) {
            return false;
        }

        $normalized = $role instanceof BackedEnum ? $role->value : $role;

        return $membership->role === $normalized;
    }

    public function isAdmin(Model $member): bool
    {
        $membership = app(FindMembership::class)->execute($member, $this);

        if (! $membership) {
            return false;
        }

        return in_array($membership->role, config('membership.admin_roles', ['owner', 'admin']));
    }

    public function membershipFor(Model $member): ?Membership
    {
        return app(FindMembership::class)->execute($member, $this);
    }

    public function membersWithRole(string|BackedEnum $role): Collection
    {
        return $this->memberships()
            ->withRole($role)
            ->with('member')
            ->get()
            ->pluck('member');
    }

    public function admins(): Collection
    {
        return $this->memberships()
            ->admins()
            ->with('member')
            ->get()
            ->pluck('member');
    }

    public function owners(): Collection
    {
        return $this->memberships()
            ->owners()
            ->with('member')
            ->get()
            ->pluck('member');
    }
}
