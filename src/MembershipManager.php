<?php

declare(strict_types=1);

namespace Vimatech\Membership;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Vimatech\Membership\Actions\AddMember;
use Vimatech\Membership\Actions\RemoveMember;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Queries\FindMembership;

final class MembershipManager
{
    public function __construct(
        private readonly AddMember $addMember,
        private readonly RemoveMember $removeMember,
        private readonly FindMembership $findMembership,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function add(
        Model $member,
        Model $membershipable,
        string|BackedEnum $role,
        ?Model $invitedBy = null,
        array $metadata = [],
    ): Membership {
        return $this->addMember->execute(
            membershipable: $membershipable,
            member: $member,
            role: $role,
            invitedBy: $invitedBy,
            metadata: $metadata,
        );
    }

    public function remove(Model $member, Model $membershipable): bool
    {
        return $this->removeMember->execute(
            membershipable: $membershipable,
            member: $member,
        );
    }

    public function has(Model $member, Model $membershipable): bool
    {
        return $this->findMembership->execute($member, $membershipable) !== null;
    }

    public function role(Model $member, Model $membershipable): ?string
    {
        $membership = $this->findMembership->execute($member, $membershipable);

        return $membership?->role;
    }
}
