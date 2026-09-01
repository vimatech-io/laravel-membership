<?php

declare(strict_types=1);

use Vimatech\Membership\Actions\EnsureRoleCanBeChanged;
use Vimatech\Membership\Exceptions\UnsupportedRoleHierarchyException;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Tests\Fixtures\Organization;
use Vimatech\Membership\Tests\Fixtures\User;

function membershipFor(User $user, Organization $org, string $role): Membership
{
    return Membership::query()->create([
        'member_type' => $user->getMorphClass(),
        'member_id' => $user->getKey(),
        'membershipable_type' => $org->getMorphClass(),
        'membershipable_id' => $org->getKey(),
        'role' => $role,
    ]);
}

it('refuses an escalation to a role it cannot rank instead of allowing it', function () {
    config()->set('membership.guards.prevent_role_escalation', true);

    $org = Organization::query()->create(['name' => 'Acme']);
    $actor = User::query()->create(['name' => 'Actor']);
    $target = User::query()->create(['name' => 'Target']);

    membershipFor($actor, $org, 'admin');
    $targetMembership = membershipFor($target, $org, 'member');

    // 'billing' is absent from membership.roles, so it has no level to compare
    expect(fn () => app(EnsureRoleCanBeChanged::class)->execute($targetMembership, 'billing', $actor))
        ->toThrow(UnsupportedRoleHierarchyException::class);
});

it('refuses a self-demotion it cannot rank instead of allowing it', function () {
    config()->set('membership.guards.prevent_self_demotion', true);

    $org = Organization::query()->create(['name' => 'Acme']);
    $actor = User::query()->create(['name' => 'Actor']);

    $own = membershipFor($actor, $org, 'owner');

    expect(fn () => app(EnsureRoleCanBeChanged::class)->execute($own, 'billing', $actor))
        ->toThrow(UnsupportedRoleHierarchyException::class);
});
