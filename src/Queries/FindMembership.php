<?php

declare(strict_types=1);

namespace Vimatech\Membership\Queries;

use Illuminate\Database\Eloquent\Model;
use Vimatech\Membership\Models\Membership;

final class FindMembership
{
    /** @var array<string, Membership|null> */
    private array $cache = [];

    public function execute(Model $member, Model $membershipable): ?Membership
    {
        $key = $this->cacheKey($member, $membershipable);

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $model = config('membership.models.membership', Membership::class);

        return $this->cache[$key] = $model::query()
            ->forMember($member)
            ->forMembershipable($membershipable)
            ->first();
    }

    public function forget(Model $member, Model $membershipable): void
    {
        unset($this->cache[$this->cacheKey($member, $membershipable)]);
    }

    public function flush(): void
    {
        $this->cache = [];
    }

    private function cacheKey(Model $member, Model $membershipable): string
    {
        return $member->getMorphClass().':'.$member->getKey()
            .'|'.$membershipable->getMorphClass().':'.$membershipable->getKey();
    }
}
