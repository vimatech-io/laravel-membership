<?php

declare(strict_types=1);

namespace Vimatech\Membership\Actions;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Vimatech\Membership\Events\MemberRoleUpdated;
use Vimatech\Membership\Exceptions\MembershipNotFoundException;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Queries\FindMembership;
use Vimatech\Membership\Support\RoleComparator;

final class UpdateMemberRole
{
    public function __construct(
        private readonly RoleComparator $roleComparator,
        private readonly EnsureNotLastOwner $ensureNotLastOwner,
        private readonly EnsureNotLastAdmin $ensureNotLastAdmin,
        private readonly EnsureRoleCanBeChanged $ensureRoleCanBeChanged,
        private readonly FindMembership $findMembership,
    ) {}

    public function execute(
        Model $membershipable,
        Model $member,
        string|BackedEnum $role,
        ?Model $actor = null,
    ): Membership {
        $model = config('membership.models.membership', Membership::class);

        $membership = $model::query()
            ->forMember($member)
            ->forMembershipable($membershipable)
            ->first();

        if (! $membership) {
            throw new MembershipNotFoundException;
        }

        $newRole = $this->roleComparator->normalize($role);
        $oldRole = $membership->role;

        if ($oldRole === $newRole) {
            return $membership;
        }

        $this->ensureRoleCanBeChanged->execute($membership, $role, $actor);
        $this->ensureNotLastOwner->execute($membership);
        $this->ensureNotLastAdmin->execute($membership);

        DB::beginTransaction();

        try {
            $membership->update(['role' => $newRole]);

            $this->findMembership->forget($member, $membershipable);

            MemberRoleUpdated::dispatch($membership, $oldRole, $newRole, $actor);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return $membership;
    }
}
