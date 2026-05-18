<?php

declare(strict_types=1);

namespace Vimatech\Membership\Actions;

use Vimatech\Membership\Exceptions\CannotRemoveLastAdminException;
use Vimatech\Membership\Models\Membership;

final class EnsureNotLastAdmin
{
    public function execute(Membership $membership): void
    {
        if (! config('membership.guards.prevent_removing_last_admin', false)) {
            return;
        }

        $adminRoles = config('membership.admin_roles', ['owner', 'admin']);

        if (! in_array($membership->role, $adminRoles)) {
            return;
        }

        $model = config('membership.models.membership', Membership::class);

        $adminCount = $model::query()
            ->where('membershipable_type', $membership->membershipable_type)
            ->where('membershipable_id', $membership->membershipable_id)
            ->whereIn('role', $adminRoles)
            ->count();

        if ($adminCount <= 1) {
            throw new CannotRemoveLastAdminException;
        }
    }
}
