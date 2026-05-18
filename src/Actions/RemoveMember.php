<?php

declare(strict_types=1);

namespace Vimatech\Membership\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Vimatech\Membership\Events\MemberRemoved;
use Vimatech\Membership\Exceptions\MembershipNotFoundException;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Queries\FindMembership;

final class RemoveMember
{
    public function __construct(
        private readonly EnsureNotLastOwner $ensureNotLastOwner,
        private readonly EnsureNotLastAdmin $ensureNotLastAdmin,
        private readonly FindMembership $findMembership,
    ) {}

    public function execute(
        Model $membershipable,
        Model $member,
        ?Model $actor = null,
    ): bool {
        $model = config('membership.models.membership', Membership::class);

        $membership = $model::query()
            ->forMember($member)
            ->forMembershipable($membershipable)
            ->first();

        if (! $membership) {
            throw new MembershipNotFoundException;
        }

        $this->ensureNotLastOwner->execute($membership);
        $this->ensureNotLastAdmin->execute($membership);

        DB::beginTransaction();

        try {
            $deleted = $membership->delete();

            $this->findMembership->forget($member, $membershipable);

            MemberRemoved::dispatch($membership, $actor);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return (bool) $deleted;
    }
}
