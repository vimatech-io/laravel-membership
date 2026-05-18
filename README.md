# Laravel Membership

[![Tests](https://github.com/vimatech-io/laravel-membership/actions/workflows/tests.yml/badge.svg)](https://github.com/vimatech-io/laravel-membership/actions/workflows/tests.yml)
[![PHPStan](https://github.com/vimatech-io/laravel-membership/actions/workflows/phpstan.yml/badge.svg)](https://github.com/vimatech-io/laravel-membership/actions/workflows/phpstan.yml)
[![Pint](https://github.com/vimatech-io/laravel-membership/actions/workflows/pint.yml/badge.svg)](https://github.com/vimatech-io/laravel-membership/actions/workflows/pint.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/vimatech/laravel-membership.svg)](https://packagist.org/packages/vimatech/laravel-membership)
[![Total Downloads](https://img.shields.io/packagist/dt/vimatech/laravel-membership.svg)](https://packagist.org/packages/vimatech/laravel-membership)
[![License](https://img.shields.io/packagist/l/vimatech/laravel-membership.svg)](https://packagist.org/packages/vimatech/laravel-membership)

**Polymorphic memberships for Laravel.**

Laravel Membership lets you attach members and roles to any Eloquent model — organizations, teams, projects, workspaces, communities, or anything else.

It answers **who belongs to what** and **with which role** — nothing more.

## Why Laravel Membership?

Most Laravel apps eventually need to answer:

- Who belongs to this organization?
- What role does this user have in this project?
- Can we prevent removing the last owner?
- Can the same membership logic work for teams, projects and workspaces?

Laravel Membership provides a small backend-only layer for that.

## Feature Matrix

| Feature | Supported |
|---|---|
| Polymorphic memberships | ✅ |
| Enum roles | ✅ |
| Role hierarchy | ✅ |
| Guards (last owner, etc.) | ✅ |
| Events | ✅ |
| Scopes | ✅ |
| Soft deletes | ✅ |
| Policy helpers | ✅ |
| Invitations | ❌ |
| Permissions | ❌ (use Spatie) |
| Billing | ❌ |
| UI | ❌ |

## Laravel Membership vs Permissions

Laravel Membership manages:
- **who** belongs to **what**
- **which role** they have

Permission packages (like Spatie) manage:
- **what** users **can do**

They are complementary, not competing.

## Use Cases

- SaaS organizations
- Teams
- Projects
- Workspaces
- Communities
- Collaborative apps
- Multi-tenant applications
- Agency client portals
- Internal company tools

## Installation

### Requirements

- PHP 8.3+
- Laravel 11, 12 or 13

```bash
composer require vimatech/laravel-membership
```

### Publish config

```bash
php artisan vendor:publish --tag=membership-config
```

### Publish migrations

```bash
php artisan vendor:publish --tag=membership-migrations
php artisan migrate
```

## Usage

### Make a model have members

```php
use Vimatech\Membership\Concerns\HasMembers;

class Organization extends Model
{
    use HasMembers;
}
```

### Make a model act as a member

```php
use Vimatech\Membership\Concerns\HasMemberships;

class User extends Authenticatable
{
    use HasMemberships;
}
```

### Create a roles enum (optional)

```php
use Vimatech\Membership\Contracts\MembershipRole;

enum OrganizationRole: string implements MembershipRole
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function level(): int
    {
        return match ($this) {
            self::Owner => 100,
            self::Admin => 50,
            self::Member => 10,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
        };
    }
}
```

You can also use plain strings — enums are optional.

### Add a member

```php
$organization->addMember($user, OrganizationRole::Owner);

// With metadata
$organization->addMember($user, OrganizationRole::Member, metadata: ['source' => 'invitation']);

// With invited_by
$organization->addMember($user, OrganizationRole::Member, invitedBy: $currentUser);
```

### Remove a member

```php
$organization->removeMember($user);
```

### Update a role

```php
$organization->updateMemberRole($user, OrganizationRole::Admin);
```

### Check membership

```php
// From entity side
$organization->hasMember($user);
$organization->isAdmin($user);
$organization->hasMemberWithRole($user, OrganizationRole::Owner);

// From member side
$user->isMemberOf($organization);
$user->hasRole($organization, OrganizationRole::Admin);
$user->hasAnyRole($organization, [OrganizationRole::Admin, OrganizationRole::Owner]);
$user->hasRoleAtLeast($organization, OrganizationRole::Member);
```

### Query members

```php
$organization->members();
$organization->membersWithRole(OrganizationRole::Admin);
$organization->admins();
$organization->owners();
```

### Query memberships from member side

```php
$user->memberships()->get();
$user->membershipFor($organization);
$user->ownedMemberships()->get();
$user->adminMemberships()->get();
$user->membershipables();
```

### Scopes

```php
use Vimatech\Membership\Models\Membership;

Membership::forMember($user)->get();
Membership::forMembershipable($organization)->get();
Membership::withRole(OrganizationRole::Admin)->get();
Membership::withAnyRole([OrganizationRole::Admin, OrganizationRole::Owner])->get();
Membership::owners()->get();
Membership::admins()->get();
Membership::joined()->get();
Membership::recent()->get();
```

### Policy helpers

```php
use Vimatech\Membership\Support\MembershipGate;

// In a Policy
public function update(User $user, Project $project): bool
{
    return MembershipGate::for($user)->isAdmin($project);
}

public function delete(User $user, Organization $organization): bool
{
    return MembershipGate::for($user)->isOwner($organization);
}

public function view(User $user, Project $project): bool
{
    return MembershipGate::for($user)->isMemberOf($project);
}

// Check a specific role
MembershipGate::for($user)->hasRole($project, 'editor');
```

### Facade (optional)

```php
use Vimatech\Membership\Facades\Membership;

Membership::add($user, $organization, 'admin');
Membership::remove($user, $organization);
Membership::has($user, $organization);
Membership::role($user, $organization); // returns 'admin'
```

## Complete Example

```php
use Vimatech\Membership\Concerns\HasMembers;
use Vimatech\Membership\Concerns\HasMemberships;
use Vimatech\Membership\Contracts\MembershipRole;

// 1. Define your models
class Organization extends Model
{
    use HasMembers;
}

class User extends Authenticatable
{
    use HasMemberships;
}

// 2. Define your roles
enum OrganizationRole: string implements MembershipRole
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function level(): int
    {
        return match ($this) {
            self::Owner => 100,
            self::Admin => 50,
            self::Member => 10,
        };
    }

    public function label(): string
    {
        return $this->name;
    }
}

// 3. Use it
$organization = Organization::create(['name' => 'Vimatech']);
$user = User::create(['name' => 'Adel']);

$organization->addMember($user, OrganizationRole::Owner);

$user->isMemberOf($organization);                              // true
$user->hasRole($organization, OrganizationRole::Owner);        // true
$user->hasRoleAtLeast($organization, OrganizationRole::Admin); // true

$organization->isAdmin($user);  // true (owner is admin)
$organization->owners();        // Collection with $user
```

## Events

The following events are dispatched:

| Event | When |
|-------|------|
| `MemberAdded` | After a member is added |
| `MemberRemoved` | After a member is removed |
| `MemberRoleUpdated` | After a member's role is changed |

Each event contains the `Membership` instance and an optional `$actor`.

## Guards

Guards are configurable protections in `config/membership.php`:

```php
'guards' => [
    'prevent_removing_last_owner' => true,   // Enabled by default
    'prevent_removing_last_admin' => false,
    'prevent_self_demotion' => false,
    'prevent_role_escalation' => false,
],
```

When `prevent_self_demotion` or `prevent_role_escalation` are enabled, pass an `$actor`:

```php
$organization->updateMemberRole(
    member: $user,
    role: OrganizationRole::Member,
    actor: $currentUser,
);
```

## Configuration

```php
// config/membership.php

return [
    'models' => [
        'membership' => \Vimatech\Membership\Models\Membership::class,
    ],

    'tables' => [
        'memberships' => 'memberships',
    ],

    // Fallback role levels (used when roles are plain strings)
    'roles' => [
        'owner' => 100,
        'admin' => 50,
        'member' => 10,
    ],

    'owner_roles' => ['owner'],
    'admin_roles' => ['owner', 'admin'],

    'guards' => [
        'prevent_removing_last_owner' => true,
        'prevent_removing_last_admin' => false,
        'prevent_self_demotion' => false,
        'prevent_role_escalation' => false,
    ],

    'soft_deletes' => false,
];
```

## Soft Deletes

By default, removing a member permanently deletes the row. To keep membership history instead, enable soft deletes:

1. Set `'soft_deletes' => true` in `config/membership.php`

2. If your app is already in production, create a migration:

```bash
php artisan make:migration add_soft_deletes_to_memberships_table
```

```php
Schema::table('memberships', function (Blueprint $table) {
    $table->softDeletes();
});
```

> If you enable `soft_deletes` **before** running your initial migration, the column is added automatically.

Once enabled:

```php
$organization->removeMember($user); // soft deletes (sets deleted_at)

$membership = Membership::withoutGlobalScopes()->forMember($user)->first();
$membership->restore();             // restores the membership
$membership->forceDelete();         // permanently deletes
$membership->trashed();             // true if soft deleted
```

## Philosophy

Laravel Membership is intentionally minimal.

The package focuses on:
- Memberships
- Roles
- Role hierarchy
- Membership guards

Design principles:
- Backend-only, UI agnostic
- No auth assumptions
- No User model assumptions
- No permissions system
- No billing assumptions
- Enum-friendly roles
- Polymorphic by default
- Laravel-native API
- Clean and testable actions

It does not aim to become a permissions framework, a billing system, a UI framework, or a complete SaaS platform.

## Possible Future Extensions

- Invitation bridge
- Audit logs
- Membership expiration
- Filament integrations
- Livewire components

Future extensions may be released as separate packages to keep the core package small and focused.

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome.

Please ensure:
- Tests pass (`composer test`)
- PHPStan passes (`composer analyse`)
- Code style is formatted with Pint (`composer format`)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

Built and maintained by [Vimatech](https://vimatech.io).
Created by [Adel Zemzemi](https://github.com/adelzemzemi).
