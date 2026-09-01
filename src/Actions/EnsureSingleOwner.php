<?php

declare(strict_types=1);

namespace Vimatech\Membership\Actions;

use Illuminate\Database\Eloquent\Model;
use Vimatech\Membership\Exceptions\CannotAddMultipleOwnersException;
use Vimatech\Membership\Models\Membership;

final class EnsureSingleOwner
{
    public function execute(Model $membershipable, string $role): void
    {
        if (config('membership.allow_multiple_owners', false)) {
            return;
        }

        $ownerRoles = config('membership.owner_roles', ['owner']);

        if (! in_array($role, $ownerRoles)) {
            return;
        }

        $model = config('membership.models.membership', Membership::class);

        $ownerExists = $model::query()
            ->where('membershipable_type', $membershipable->getMorphClass())
            ->where('membershipable_id', $membershipable->getKey())
            ->whereIn('role', $ownerRoles)
            ->exists();

        if ($ownerExists) {
            throw new CannotAddMultipleOwnersException;
        }
    }
}
