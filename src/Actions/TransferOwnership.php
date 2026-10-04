<?php

declare(strict_types=1);

namespace Vimatech\Membership\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Vimatech\Membership\Events\OwnershipTransferred;
use Vimatech\Membership\Exceptions\CannotTransferOwnershipException;
use Vimatech\Membership\Exceptions\MembershipNotFoundException;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Queries\FindMembership;

final class TransferOwnership
{
    public function __construct(
        private readonly FindMembership $findMembership,
    ) {}

    public function execute(
        Model $membershipable,
        Model $newOwner,
        ?Model $actor = null,
    ): void {
        $model = config('membership.models.membership', Membership::class);
        $ownerRoles = config('membership.owner_roles', ['owner']);
        $ownerRole = $ownerRoles[0] ?? throw CannotTransferOwnershipException::withoutOwnerRole();

        $newOwnerMembership = $this->findMembership->execute($newOwner, $membershipable);

        if (! $newOwnerMembership) {
            throw new MembershipNotFoundException;
        }

        $currentOwnerMembership = $model::query()
            ->where('membershipable_type', $membershipable->getMorphClass())
            ->where('membershipable_id', $membershipable->getKey())
            ->whereIn('role', $ownerRoles)
            ->oldest()
            ->first();

        if ($currentOwnerMembership?->is($newOwnerMembership)) {
            throw CannotTransferOwnershipException::toCurrentOwner();
        }

        $demoteToRole = $currentOwnerMembership ? $this->demotionRole($ownerRoles) : null;

        DB::beginTransaction();

        try {
            if ($currentOwnerMembership) {
                $currentOwnerMembership->update(['role' => $demoteToRole]);
            }

            $newOwnerMembership->update(['role' => $ownerRole]);

            $this->findMembership->forget($newOwner, $membershipable);

            if ($currentOwnerMembership) {
                $this->findMembership->forget($currentOwnerMembership->member, $membershipable);
            }

            OwnershipTransferred::dispatch(
                $currentOwnerMembership ?? $newOwnerMembership,
                $newOwnerMembership,
                $actor,
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * @param  array<int, string>  $ownerRoles
     */
    private function demotionRole(array $ownerRoles): string
    {
        foreach (config('membership.admin_roles', ['owner', 'admin']) as $role) {
            if (! in_array($role, $ownerRoles, true)) {
                return $role;
            }
        }

        throw CannotTransferOwnershipException::withoutDemotionRole();
    }
}
