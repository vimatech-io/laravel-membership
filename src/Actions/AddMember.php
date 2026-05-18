<?php

declare(strict_types=1);

namespace Vimatech\Membership\Actions;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Vimatech\Membership\Events\MemberAdded;
use Vimatech\Membership\Exceptions\AlreadyMemberException;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Queries\FindMembership;
use Vimatech\Membership\Support\RoleComparator;

final class AddMember
{
    public function __construct(
        private readonly RoleComparator $roleComparator,
        private readonly FindMembership $findMembership,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        Model $membershipable,
        Model $member,
        string|BackedEnum $role,
        ?Model $invitedBy = null,
        array $metadata = [],
        ?Model $actor = null,
    ): Membership {
        $model = config('membership.models.membership', Membership::class);

        $exists = $model::query()
            ->forMember($member)
            ->forMembershipable($membershipable)
            ->exists();

        if ($exists) {
            throw new AlreadyMemberException;
        }

        DB::beginTransaction();

        try {
            $membership = $model::create([
                'member_type' => $member->getMorphClass(),
                'member_id' => $member->getKey(),
                'membershipable_type' => $membershipable->getMorphClass(),
                'membershipable_id' => $membershipable->getKey(),
                'role' => $this->roleComparator->normalize($role),
                'joined_at' => now(),
                'invited_by_type' => $invitedBy?->getMorphClass(),
                'invited_by_id' => $invitedBy?->getKey(),
                'metadata' => $metadata ?: null,
            ]);

            $this->findMembership->forget($member, $membershipable);

            MemberAdded::dispatch($membership, $actor);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return $membership;
    }
}
