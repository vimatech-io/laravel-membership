<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Vimatech\Membership\Actions\AddMember;
use Vimatech\Membership\Actions\TransferOwnership;
use Vimatech\Membership\Events\OwnershipTransferred;
use Vimatech\Membership\Exceptions\CannotTransferOwnershipException;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Tests\Fixtures\Organization;
use Vimatech\Membership\Tests\Fixtures\OrganizationRole;
use Vimatech\Membership\Tests\Fixtures\User;

beforeEach(function () {
    $this->transfer = app(TransferOwnership::class);
    $this->organization = Organization::create(['name' => 'Vimatech']);
    $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.com']);
    $this->member = User::create(['name' => 'Member', 'email' => 'member@example.com']);

    app(AddMember::class)->execute($this->organization, $this->owner, OrganizationRole::Owner);
    app(AddMember::class)->execute($this->organization, $this->member, OrganizationRole::Member);
});

function roleOf(User $user, Organization $organization): string
{
    return Membership::query()->forMember($user)->forMembershipable($organization)->value('role');
}

it('hands ownership to the new owner and demotes the previous one', function () {
    Event::fake([OwnershipTransferred::class]);

    $this->transfer->execute($this->organization, $this->member);

    expect(roleOf($this->owner, $this->organization))->toBe('admin')
        ->and(roleOf($this->member, $this->organization))->toBe('owner');

    Event::assertDispatched(OwnershipTransferred::class, fn (OwnershipTransferred $event) => $event->previousOwnerMembership->member_id === $this->owner->id
        && $event->newOwnerMembership->member_id === $this->member->id);
});

it('refuses to transfer ownership to the current owner', function () {
    Event::fake([OwnershipTransferred::class]);

    expect(fn () => $this->transfer->execute($this->organization, $this->owner))
        ->toThrow(CannotTransferOwnershipException::class, 'already holds it');

    expect(roleOf($this->owner, $this->organization))->toBe('owner');
    Event::assertNotDispatched(OwnershipTransferred::class);
});

it('refuses when no admin role outside the owner roles is configured', function () {
    config(['membership.admin_roles' => ['owner']]);
    Event::fake([OwnershipTransferred::class]);

    expect(fn () => $this->transfer->execute($this->organization, $this->member))
        ->toThrow(CannotTransferOwnershipException::class, 'membership.admin_roles');

    expect(roleOf($this->owner, $this->organization))->toBe('owner')
        ->and(roleOf($this->member, $this->organization))->toBe('member');
    Event::assertNotDispatched(OwnershipTransferred::class);
});

it('demotes to the first admin role that is not an owner role, whatever its position', function () {
    config(['membership.admin_roles' => ['admin', 'owner']]);

    $this->transfer->execute($this->organization, $this->member);

    expect(roleOf($this->owner, $this->organization))->toBe('admin')
        ->and(roleOf($this->member, $this->organization))->toBe('owner');
});

it('refuses when no owner role is configured', function () {
    config(['membership.owner_roles' => []]);

    expect(fn () => $this->transfer->execute($this->organization, $this->member))
        ->toThrow(CannotTransferOwnershipException::class, 'membership.owner_roles');

    expect(roleOf($this->member, $this->organization))->toBe('member');
});
