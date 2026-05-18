<?php

declare(strict_types=1);

namespace Vimatech\Membership\Facades;

use Illuminate\Support\Facades\Facade;
use Vimatech\Membership\MembershipManager;

/**
 * @method static \Vimatech\Membership\Models\Membership add(\Illuminate\Database\Eloquent\Model $member, \Illuminate\Database\Eloquent\Model $membershipable, string|\BackedEnum $role, ?\Illuminate\Database\Eloquent\Model $invitedBy = null, array<string, mixed> $metadata = [])
 * @method static bool remove(\Illuminate\Database\Eloquent\Model $member, \Illuminate\Database\Eloquent\Model $membershipable)
 * @method static bool has(\Illuminate\Database\Eloquent\Model $member, \Illuminate\Database\Eloquent\Model $membershipable)
 * @method static ?string role(\Illuminate\Database\Eloquent\Model $member, \Illuminate\Database\Eloquent\Model $membershipable)
 *
 * @see MembershipManager
 */
final class Membership extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MembershipManager::class;
    }
}
