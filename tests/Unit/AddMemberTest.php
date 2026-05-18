<?php

declare(strict_types=1);

use Vimatech\Membership\Actions\AddMember;
use Vimatech\Membership\Exceptions\AlreadyMemberException;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Tests\Fixtures\Organization;
use Vimatech\Membership\Tests\Fixtures\OrganizationRole;
use Vimatech\Membership\Tests\Fixtures\User;

beforeEach(function () {
    $this->action = app(AddMember::class);
    $this->user = User::create(['name' => 'Adel', 'email' => 'adel@example.com']);
    $this->organization = Organization::create(['name' => 'Vimatech']);
});

it('creates a membership', function () {
    $membership = $this->action->execute($this->organization, $this->user, OrganizationRole::Owner);

    expect($membership)->toBeInstanceOf(Membership::class)
        ->and($membership->role)->toBe('owner')
        ->and($membership->joined_at)->not->toBeNull();
});

it('throws if already a member', function () {
    $this->action->execute($this->organization, $this->user, OrganizationRole::Owner);
    $this->action->execute($this->organization, $this->user, OrganizationRole::Admin);
})->throws(AlreadyMemberException::class);

it('accepts string roles', function () {
    $membership = $this->action->execute($this->organization, $this->user, 'viewer');

    expect($membership->role)->toBe('viewer');
});

it('stores metadata', function () {
    $membership = $this->action->execute(
        $this->organization,
        $this->user,
        OrganizationRole::Member,
        metadata: ['source' => 'api'],
    );

    expect($membership->metadata)->toBe(['source' => 'api']);
});
