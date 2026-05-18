<?php

declare(strict_types=1);

namespace Vimatech\Membership\Models;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $member_type
 * @property int $member_id
 * @property string $membershipable_type
 * @property int $membershipable_id
 * @property string $role
 * @property Carbon|null $joined_at
 * @property string|null $invited_by_type
 * @property int|null $invited_by_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Model $member
 * @property-read Model $membershipable
 * @property-read Model|null $invitedBy
 *
 * @method static Builder<static> forMember(Model $member)
 * @method static Builder<static> forMembershipable(Model $membershipable)
 * @method static Builder<static> withRole(string|BackedEnum $role)
 * @method static Builder<static> withAnyRole(array<int, string|BackedEnum> $roles)
 * @method static Builder<static> owners()
 * @method static Builder<static> admins()
 * @method static Builder<static> joined()
 * @method static Builder<static> recent()
 */
class Membership extends Model
{
    protected $guarded = [];

    protected bool $forceDeleting = false;

    protected $casts = [
        'joined_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('membership.tables.memberships', 'memberships');
    }

    protected static function booted(): void
    {
        if (config('membership.soft_deletes', false)) {
            static::addGlobalScope(new SoftDeletingScope);
        }
    }

    protected function performDeleteOnModel(): void
    {
        if (config('membership.soft_deletes', false) && ! $this->forceDeleting) {
            $this->runSoftDelete();

            return;
        }

        parent::performDeleteOnModel();
    }

    public function restore(): bool
    {
        if (! config('membership.soft_deletes', false)) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = null;
        $this->exists = true;

        return $this->save();
    }

    public function forceDelete(): ?bool
    {
        $this->forceDeleting = true;

        return tap($this->delete(), function () {
            $this->forceDeleting = false;
        });
    }

    public function trashed(): bool
    {
        if (! config('membership.soft_deletes', false)) {
            return false;
        }

        return ! is_null($this->{$this->getDeletedAtColumn()});
    }

    public function getDeletedAtColumn(): string
    {
        return 'deleted_at';
    }

    public function getQualifiedDeletedAtColumn(): string
    {
        return $this->qualifyColumn($this->getDeletedAtColumn());
    }

    protected function runSoftDelete(): void
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery()); // @phpstan-ignore argument.type

        $time = $this->freshTimestamp();
        $columns = [$this->getDeletedAtColumn() => $this->fromDateTime($time)];

        $this->{$this->getDeletedAtColumn()} = $time;

        if ($this->usesTimestamps() && ! is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;
            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));

        $this->fireModelEvent('trashed', false);
    }

    // Relations

    /** @return MorphTo<Model, $this> */
    public function member(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function membershipable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function invitedBy(): MorphTo
    {
        return $this->morphTo('invited_by');
    }

    // Scopes

    /** @param Builder<static> $query */
    public function scopeForMember(Builder $query, Model $member): void
    {
        $query
            ->where('member_type', $member->getMorphClass())
            ->where('member_id', $member->getKey());
    }

    /** @param Builder<static> $query */
    public function scopeForMembershipable(Builder $query, Model $membershipable): void
    {
        $query
            ->where('membershipable_type', $membershipable->getMorphClass())
            ->where('membershipable_id', $membershipable->getKey());
    }

    /** @param Builder<static> $query */
    public function scopeWithRole(Builder $query, string|BackedEnum $role): void
    {
        $query->where('role', $role instanceof BackedEnum ? $role->value : $role);
    }

    /**
     * @param  Builder<static>  $query
     * @param  array<int, string|BackedEnum>  $roles
     */
    public function scopeWithAnyRole(Builder $query, array $roles): void
    {
        $normalized = array_map(
            fn (string|BackedEnum $role) => $role instanceof BackedEnum ? $role->value : $role,
            $roles
        );

        $query->whereIn('role', $normalized);
    }

    /** @param Builder<static> $query */
    public function scopeOwners(Builder $query): void
    {
        $query->whereIn('role', config('membership.owner_roles', ['owner']));
    }

    /** @param Builder<static> $query */
    public function scopeAdmins(Builder $query): void
    {
        $query->whereIn('role', config('membership.admin_roles', ['owner', 'admin']));
    }

    /** @param Builder<static> $query */
    public function scopeJoined(Builder $query): void
    {
        $query->whereNotNull('joined_at');
    }

    /** @param Builder<static> $query */
    public function scopeRecent(Builder $query): void
    {
        $query->orderByDesc('created_at');
    }
}
