<?php

declare(strict_types=1);

namespace Vimatech\Membership\Actions;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Vimatech\Membership\Exceptions\RoleEscalationException;
use Vimatech\Membership\Exceptions\SelfDemotionException;
use Vimatech\Membership\Models\Membership;
use Vimatech\Membership\Support\RoleComparator;

final class EnsureRoleCanBeChanged
{
    public function __construct(
        private readonly RoleComparator $roleComparator,
    ) {}

    public function execute(Membership $membership, string|BackedEnum $newRole, ?Model $actor = null): void
    {
        if (! $actor) {
            return;
        }

        $this->checkSelfDemotion($membership, $newRole, $actor);
        $this->checkRoleEscalation($membership, $newRole, $actor);
    }

    private function checkSelfDemotion(Membership $membership, string|BackedEnum $newRole, Model $actor): void
    {
        if (! config('membership.guards.prevent_self_demotion', false)) {
            return;
        }

        $isSelf = $membership->member_type === $actor->getMorphClass()
            && $membership->member_id == $actor->getKey();

        if (! $isSelf) {
            return;
        }

        $currentLevel = $this->roleComparator->level($membership->role);
        $newLevel = $this->roleComparator->level($newRole);

        if ($currentLevel !== null && $newLevel !== null && $newLevel < $currentLevel) {
            throw new SelfDemotionException;
        }
    }

    private function checkRoleEscalation(Membership $membership, string|BackedEnum $newRole, Model $actor): void
    {
        if (! config('membership.guards.prevent_role_escalation', false)) {
            return;
        }

        $model = config('membership.models.membership', Membership::class);

        $actorMembership = $model::query()
            ->where('member_type', $actor->getMorphClass())
            ->where('member_id', $actor->getKey())
            ->where('membershipable_type', $membership->membershipable_type)
            ->where('membershipable_id', $membership->membershipable_id)
            ->first();

        if (! $actorMembership) {
            return;
        }

        $actorLevel = $this->roleComparator->level($actorMembership->role);
        $newLevel = $this->roleComparator->level($newRole);

        if ($actorLevel !== null && $newLevel !== null && $newLevel > $actorLevel) {
            throw new RoleEscalationException;
        }
    }
}
