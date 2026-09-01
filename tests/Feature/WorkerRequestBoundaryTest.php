<?php

declare(strict_types=1);

use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Queries\FindMembership;
use Vimatech\Membership\Tests\Fixtures\Organization;
use Vimatech\Membership\Tests\Fixtures\User;

function makeMembership(User $user, Organization $org, string $role = 'admin'): Membership
{
    return Membership::query()->create([
        'member_type' => $user->getMorphClass(),
        'member_id' => $user->getKey(),
        'membershipable_type' => $org->getMorphClass(),
        'membershipable_id' => $org->getKey(),
        'role' => $role,
    ]);
}

it('does not answer from a previous request cache after a membership is revoked', function () {
    $user = User::query()->create(['name' => 'A']);
    $org = Organization::query()->create(['name' => 'O']);
    $membership = makeMembership($user, $org);

    expect(app(FindMembership::class)->execute($user, $org))->not->toBeNull();

    // request ends; a bare worker loop does not drop scoped instances
    app()->terminate();

    // revoked out of band: another process, another worker, a direct query
    $membership->delete();

    expect(app(FindMembership::class)->execute($user, $org))->toBeNull();
});

it('does not answer from a previous request cache after a membership is granted', function () {
    $user = User::query()->create(['name' => 'A']);
    $org = Organization::query()->create(['name' => 'O']);

    expect(app(FindMembership::class)->execute($user, $org))->toBeNull();

    app()->terminate();

    makeMembership($user, $org);

    expect(app(FindMembership::class)->execute($user, $org))->not->toBeNull();
});
