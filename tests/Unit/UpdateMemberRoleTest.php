<?php

declare(strict_types=1);

use Vimatech\Membership\Actions\AddMember;
use Vimatech\Membership\Actions\UpdateMemberRole;
use Vimatech\Membership\Exceptions\CannotRemoveLastOwnerException;
use Vimatech\Membership\Exceptions\MembershipNotFoundException;
use Vimatech\Membership\Tests\Fixtures\Organization;
use Vimatech\Membership\Tests\Fixtures\OrganizationRole;
use Vimatech\Membership\Tests\Fixtures\User;

beforeEach(function () {
    $this->addMember = app(AddMember::class);
    $this->updateRole = app(UpdateMemberRole::class);
    $this->user = User::create(['name' => 'Adel', 'email' => 'adel@example.com']);
    $this->organization = Organization::create(['name' => 'Vimatech']);
});

it('updates a member role', function () {
    $this->addMember->execute($this->organization, $this->user, OrganizationRole::Member);

    $membership = $this->updateRole->execute($this->organization, $this->user, OrganizationRole::Admin);

    expect($membership->role)->toBe('admin');
});

it('throws if membership not found', function () {
    $this->updateRole->execute($this->organization, $this->user, OrganizationRole::Admin);
})->throws(MembershipNotFoundException::class);

it('prevents demoting last owner', function () {
    $this->addMember->execute($this->organization, $this->user, OrganizationRole::Owner);

    $this->updateRole->execute($this->organization, $this->user, OrganizationRole::Member);
})->throws(CannotRemoveLastOwnerException::class);

it('returns same membership if role unchanged', function () {
    $this->addMember->execute($this->organization, $this->user, OrganizationRole::Admin);

    $membership = $this->updateRole->execute($this->organization, $this->user, OrganizationRole::Admin);

    expect($membership->role)->toBe('admin');
});
