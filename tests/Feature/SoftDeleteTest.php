<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Tests\Fixtures\Organization;
use Vimatech\Membership\Tests\Fixtures\OrganizationRole;
use Vimatech\Membership\Tests\Fixtures\User;

beforeEach(function () {
    config()->set('membership.soft_deletes', true);

    // Add deleted_at column to the memberships table
    Schema::table('memberships', function (Blueprint $table) {
        $table->softDeletes();
    });

    $this->user = User::create(['name' => 'Adel', 'email' => 'adel@example.com']);
    $this->organization = Organization::create(['name' => 'Vimatech']);
});

it('soft deletes a membership when soft_deletes is enabled', function () {
    $this->organization->addMember($this->user, OrganizationRole::Admin);

    $this->organization->removeMember($this->user);

    // Not in regular query
    expect($this->organization->hasMember($this->user))->toBeFalse();

    // Still in DB
    $trashed = Membership::withoutGlobalScopes()->forMember($this->user)->first();
    expect($trashed)->not->toBeNull()
        ->and($trashed->deleted_at)->not->toBeNull();
});

it('can restore a soft deleted membership', function () {
    $this->organization->addMember($this->user, OrganizationRole::Admin);

    $this->organization->removeMember($this->user);

    $trashed = Membership::withoutGlobalScopes()->forMember($this->user)->first();
    $trashed->restore();

    expect($this->organization->hasMember($this->user))->toBeTrue();
});

it('force deletes a membership', function () {
    $membership = $this->organization->addMember($this->user, OrganizationRole::Admin);

    $membership->forceDelete();

    $record = Membership::withoutGlobalScopes()->forMember($this->user)->first();
    expect($record)->toBeNull();
});

it('trashed returns false when soft_deletes is disabled', function () {
    config()->set('membership.soft_deletes', false);

    $membership = $this->organization->addMember($this->user, OrganizationRole::Admin);

    expect($membership->trashed())->toBeFalse();
});
