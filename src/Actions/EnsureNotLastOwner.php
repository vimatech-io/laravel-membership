<?php

declare(strict_types=1);

namespace Vimatech\Membership\Actions;

use Vimatech\Membership\Exceptions\CannotRemoveLastOwnerException;
use Vimatech\Membership\Models\Membership;

final class EnsureNotLastOwner
{
    public function execute(Membership $membership): void
    {
        if (! config('membership.guards.prevent_removing_last_owner', true)) {
            return;
        }

        $ownerRoles = config('membership.owner_roles', ['owner']);

        if (! in_array($membership->role, $ownerRoles)) {
            return;
        }

        $model = config('membership.models.membership', Membership::class);

        $ownerCount = $model::query()
            ->where('membershipable_type', $membership->membershipable_type)
            ->where('membershipable_id', $membership->membershipable_id)
            ->whereIn('role', $ownerRoles)
            ->count();

        if ($ownerCount <= 1) {
            throw new CannotRemoveLastOwnerException;
        }
    }
}
