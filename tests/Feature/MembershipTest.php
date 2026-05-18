<?php

declare(strict_types=1);

/**
 * @property User $user
 * @property Organization $organization
 */

use Illuminate\Support\Facades\Event;
use Vimatech\Membership\Events\MemberAdded;
use Vimatech\Membership\Events\MemberRemoved;
use Vimatech\Membership\Events\MemberRoleUpdated;
use Vimatech\Membership\Exceptions\AlreadyMemberException;
use Vimatech\Membership\Exceptions\CannotRemoveLastOwnerException;
use Vimatech\Membership\Exceptions\MembershipNotFoundException;
use Vimatech\Membership\Exceptions\UnsupportedRoleHierarchyException;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Tests\Fixtures\Organization;
use Vimatech\Membership\Tests\Fixtures\OrganizationRole;
use Vimatech\Membership\Tests\Fixtures\Project;
use Vimatech\Membership\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Adel', 'email' => 'adel@example.com']);
    $this->organization = Organization::create(['name' => 'Vimatech']);
});

it('can add a member to a membershipable model', function () {
    $membership = $this->organization->addMember($this->user, OrganizationRole::Owner);

    expect($membership)->toBeInstanceOf(Membership::class)
        ->and($membership->role)->toBe('owner')
        ->and($membership->member_id)->toBe($this->user->id)
        ->and($membership->membershipable_id)->toBe($this->organization->id);
});

it('prevents duplicate memberships', function () {
    $this->organization->addMember($this->user, OrganizationRole::Owner);

    $this->organization->addMember($this->user, OrganizationRole::Admin);
})->throws(AlreadyMemberException::class);

it('can remove a member', function () {
    $this->organization->addMember($this->user, OrganizationRole::Admin);

    $result = $this->organization->removeMember($this->user);

    expect($result)->toBeTrue()
        ->and($this->organization->hasMember($this->user))->toBeFalse();
});

it('throws when removing a non-existent member', function () {
    $this->organization->removeMember($this->user);
})->throws(MembershipNotFoundException::class);

it('can update a member role', function () {
    $this->organization->addMember($this->user, OrganizationRole::Member);

    $membership = $this->organization->updateMemberRole($this->user, OrganizationRole::Admin);

    expect($membership->role)->toBe('admin');
});

it('dispatches MemberAdded event', function () {
    Event::fake([MemberAdded::class]);

    $this->organization->addMember($this->user, OrganizationRole::Owner);

    Event::assertDispatched(MemberAdded::class, function ($event) {
        return $event->membership->member_id === $this->user->id;
    });
});

it('dispatches MemberRemoved event', function () {
    Event::fake([MemberRemoved::class]);

    $this->organization->addMember($this->user, OrganizationRole::Admin);
    $this->organization->removeMember($this->user);

    Event::assertDispatched(MemberRemoved::class);
});

it('dispatches MemberRoleUpdated event', function () {
    Event::fake([MemberRoleUpdated::class]);

    $this->organization->addMember($this->user, OrganizationRole::Member);
    $this->organization->updateMemberRole($this->user, OrganizationRole::Admin);

    Event::assertDispatched(MemberRoleUpdated::class, function ($event) {
        return $event->oldRole === 'member' && $event->newRole === 'admin';
    });
});

it('can check if a user is member of an entity', function () {
    expect($this->organization->hasMember($this->user))->toBeFalse();

    $this->organization->addMember($this->user, OrganizationRole::Member);

    expect($this->organization->hasMember($this->user))->toBeTrue();
});

it('can check if a user has a role', function () {
    $this->organization->addMember($this->user, OrganizationRole::Admin);

    expect($this->user->hasRole($this->organization, OrganizationRole::Admin))->toBeTrue()
        ->and($this->user->hasRole($this->organization, OrganizationRole::Owner))->toBeFalse();
});

it('can check role hierarchy with enums', function () {
    $this->organization->addMember($this->user, OrganizationRole::Admin);

    expect($this->user->hasRoleAtLeast($this->organization, OrganizationRole::Member))->toBeTrue()
        ->and($this->user->hasRoleAtLeast($this->organization, OrganizationRole::Admin))->toBeTrue()
        ->and($this->user->hasRoleAtLeast($this->organization, OrganizationRole::Owner))->toBeFalse();
});

it('throws when hierarchy is unsupported', function () {
    $this->organization->addMember($this->user, 'custom_role');

    $this->user->hasRoleAtLeast($this->organization, 'another_role');
})->throws(UnsupportedRoleHierarchyException::class);

it('prevents removing last owner', function () {
    $this->organization->addMember($this->user, OrganizationRole::Owner);

    $this->organization->removeMember($this->user);
})->throws(CannotRemoveLastOwnerException::class);

it('allows removing owner when another owner exists', function () {
    $otherUser = User::create(['name' => 'Other', 'email' => 'other@example.com']);

    $this->organization->addMember($this->user, OrganizationRole::Owner);
    $this->organization->addMember($otherUser, OrganizationRole::Owner);

    $result = $this->organization->removeMember($this->user);

    expect($result)->toBeTrue();
});

it('can query memberships for member', function () {
    $this->organization->addMember($this->user, OrganizationRole::Admin);

    $memberships = $this->user->memberships()->get();

    expect($memberships)->toHaveCount(1)
        ->and($memberships->first()->role)->toBe('admin');
});

it('can query memberships for membershipable', function () {
    $this->organization->addMember($this->user, OrganizationRole::Admin);

    $memberships = $this->organization->memberships()->get();

    expect($memberships)->toHaveCount(1);
});

it('supports polymorphic membershipables', function () {
    $project = Project::create(['name' => 'Laravel Bookable']);

    $this->organization->addMember($this->user, OrganizationRole::Owner);
    $project->addMember($this->user, OrganizationRole::Admin);

    expect($this->user->memberships()->count())->toBe(2)
        ->and($this->user->isMemberOf($this->organization))->toBeTrue()
        ->and($this->user->isMemberOf($project))->toBeTrue();
});

it('supports polymorphic members', function () {
    $otherUser = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

    $this->organization->addMember($this->user, OrganizationRole::Owner);
    $this->organization->addMember($otherUser, OrganizationRole::Member);

    expect($this->organization->memberships()->count())->toBe(2);
});

it('stores invited_by', function () {
    $inviter = User::create(['name' => 'Inviter', 'email' => 'inviter@example.com']);

    $membership = $this->organization->addMember(
        $this->user,
        OrganizationRole::Member,
        invitedBy: $inviter,
    );

    expect($membership->invited_by_type)->toBe($inviter->getMorphClass())
        ->and($membership->invited_by_id)->toBe($inviter->id);
});

it('stores metadata', function () {
    $membership = $this->organization->addMember(
        $this->user,
        OrganizationRole::Member,
        metadata: ['source' => 'invitation', 'campaign' => 'launch'],
    );

    expect($membership->metadata)->toBe(['source' => 'invitation', 'campaign' => 'launch']);
});

it('can get members with role', function () {
    $otherUser = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

    $this->organization->addMember($this->user, OrganizationRole::Admin);
    $this->organization->addMember($otherUser, OrganizationRole::Member);

    $admins = $this->organization->membersWithRole(OrganizationRole::Admin);

    expect($admins)->toHaveCount(1)
        ->and($admins->first()->id)->toBe($this->user->id);
});

it('can check isMemberOf from user side', function () {
    expect($this->user->isMemberOf($this->organization))->toBeFalse();

    $this->organization->addMember($this->user, OrganizationRole::Member);

    expect($this->user->isMemberOf($this->organization))->toBeTrue();
});

it('can get membershipFor from user side', function () {
    $this->organization->addMember($this->user, OrganizationRole::Admin);

    $membership = $this->user->membershipFor($this->organization);

    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe('admin');
});

it('can use hasAnyRole', function () {
    $this->organization->addMember($this->user, OrganizationRole::Admin);

    expect($this->user->hasAnyRole($this->organization, [OrganizationRole::Admin, OrganizationRole::Owner]))->toBeTrue()
        ->and($this->user->hasAnyRole($this->organization, [OrganizationRole::Member]))->toBeFalse();
});

it('can get owned memberships', function () {
    $this->organization->addMember($this->user, OrganizationRole::Owner);

    $project = Project::create(['name' => 'Side Project']);
    $project->addMember($this->user, OrganizationRole::Member);

    $owned = $this->user->ownedMemberships()->get();

    expect($owned)->toHaveCount(1)
        ->and($owned->first()->role)->toBe('owner');
});

it('can use scopes on Membership model', function () {
    $this->organization->addMember($this->user, OrganizationRole::Admin);

    $results = Membership::forMember($this->user)->get();
    expect($results)->toHaveCount(1);

    $results = Membership::forMembershipable($this->organization)->get();
    expect($results)->toHaveCount(1);

    $results = Membership::withRole(OrganizationRole::Admin)->get();
    expect($results)->toHaveCount(1);

    $results = Membership::withRole(OrganizationRole::Owner)->get();
    expect($results)->toHaveCount(0);
});

it('uses string roles directly', function () {
    $membership = $this->organization->addMember($this->user, 'editor');

    expect($membership->role)->toBe('editor')
        ->and($this->user->hasRole($this->organization, 'editor'))->toBeTrue();
});
