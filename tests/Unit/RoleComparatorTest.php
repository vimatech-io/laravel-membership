<?php

declare(strict_types=1);

use Vimatech\Membership\Exceptions\UnsupportedRoleHierarchyException;
use Vimatech\Membership\Support\RoleComparator;
use Vimatech\Membership\Tests\Fixtures\OrganizationRole;

beforeEach(function () {
    $this->comparator = new RoleComparator;
});

it('normalizes enum roles to string', function () {
    expect($this->comparator->normalize(OrganizationRole::Owner))->toBe('owner')
        ->and($this->comparator->normalize('admin'))->toBe('admin');
});

it('returns level for enum implementing MembershipRole', function () {
    expect($this->comparator->level(OrganizationRole::Owner))->toBe(100)
        ->and($this->comparator->level(OrganizationRole::Admin))->toBe(50)
        ->and($this->comparator->level(OrganizationRole::Member))->toBe(10);
});

it('returns level from config for string roles', function () {
    expect($this->comparator->level('owner'))->toBe(100)
        ->and($this->comparator->level('admin'))->toBe(50)
        ->and($this->comparator->level('member'))->toBe(10);
});

it('returns null for unknown string roles', function () {
    expect($this->comparator->level('custom_role'))->toBeNull();
});

it('checks isAtLeast correctly with enums', function () {
    expect($this->comparator->isAtLeast(OrganizationRole::Owner, OrganizationRole::Admin))->toBeTrue()
        ->and($this->comparator->isAtLeast(OrganizationRole::Admin, OrganizationRole::Owner))->toBeFalse()
        ->and($this->comparator->isAtLeast(OrganizationRole::Admin, OrganizationRole::Admin))->toBeTrue();
});

it('throws UnsupportedRoleHierarchyException for unknown roles', function () {
    $this->comparator->isAtLeast('unknown_role', 'another_unknown');
})->throws(UnsupportedRoleHierarchyException::class);
