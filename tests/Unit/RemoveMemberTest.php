<?php

declare(strict_types=1);

use Vimatech\Membership\Actions\AddMember;
use Vimatech\Membership\Actions\RemoveMember;
use Vimatech\Membership\Exceptions\CannotRemoveLastOwnerException;
use Vimatech\Membership\Exceptions\MembershipNotFoundException;
use Vimatech\Membership\Tests\Fixtures\Organization;
use Vimatech\Membership\Tests\Fixtures\OrganizationRole;
use Vimatech\Membership\Tests\Fixtures\User;

beforeEach(function () {
    $this->addMember = app(AddMember::class);
    $this->removeMember = app(RemoveMember::class);
    $this->user = User::create(['name' => 'Adel', 'email' => 'adel@example.com']);
    $this->organization = Organization::create(['name' => 'Vimatech']);
});

it('removes a member', function () {
    $this->addMember->execute($this->organization, $this->user, OrganizationRole::Admin);

    $result = $this->removeMember->execute($this->organization, $this->user);

    expect($result)->toBeTrue();
});

it('throws if membership not found', function () {
    $this->removeMember->execute($this->organization, $this->user);
})->throws(MembershipNotFoundException::class);

it('prevents removing last owner', function () {
    $this->addMember->execute($this->organization, $this->user, OrganizationRole::Owner);

    $this->removeMember->execute($this->organization, $this->user);
})->throws(CannotRemoveLastOwnerException::class);
