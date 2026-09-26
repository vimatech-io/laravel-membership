# Changelog

All notable changes to `vimatech/laravel-membership` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-09-26

### Added

- `membership.run_migrations` (default `true`). Set it to `false` to stop the package from loading its own migration, and ship the published copy in your application instead. Any value other than a boolean is refused at boot with an `InvalidArgumentException` naming the key.

### Changed

- `vendor:publish --tag=membership-migrations` now writes `database/migrations/0001_01_01_000003_create_memberships_table.php` instead of `create_memberships_table.php`. The migration the package loads itself has no timestamp prefix, and Laravel runs migrations in basename order, so it always ran after every timestamped migration of the application. On a fresh database (`migrate:fresh`, CI, a new environment) an application migration that altered `memberships` or declared a foreign key to it failed with the table missing. The published name sorts right after Laravel's own `0001_01_01` migrations, before anything the application adds. A file published by an earlier version kept the old basename, replaced the package's copy and therefore still ran last.

### Upgrading

- Nothing changes unless you opt in. `run_migrations` defaults to `true`, and the loaded migration keeps the name `create_memberships_table` that existing databases already recorded, so no migration becomes pending on upgrade.
- To have `memberships` created before your own migrations: set `'run_migrations' => false` in `config/membership.php`, then run `php artisan vendor:publish --tag=membership-migrations`. Do both. Publishing without disabling `run_migrations` runs the migration twice and fails with `table "memberships" already exists`; disabling it without publishing leaves nothing that creates the table.
- On a database that already recorded `create_memberships_table`, the published migration shows as pending and would fail on the existing table. Rename the recorded row instead of running it: `UPDATE migrations SET migration = '0001_01_01_000003_create_memberships_table' WHERE migration = 'create_memberships_table'`.
- If you published `create_memberships_table.php` with an earlier version to customise the table, that file still replaces the package's copy and still runs last. Rename it to `0001_01_01_000003_create_memberships_table.php`, set `run_migrations` to `false`, and rename the recorded row as above.

## [1.0.2] - 2026-09-01

### Fixed

- The self-demotion and role-escalation guards no longer pass silently on a role they cannot rank. Both looked the level up themselves and skipped the check whenever either side was absent from `membership.roles`, so with the guard explicitly enabled a change to a custom role outside the map was permitted with nothing raised. They now go through `RoleComparator::isAtLeast()`, which was already the loud path and already threw `UnsupportedRoleHierarchyException` for exactly this case. The guards simply were not using it.

- The membership lookup cache is cleared when the application terminates instead of relying on `scoped()` bindings being dropped between requests. Laravel clears scoped bindings in one place only, between queue jobs, so under a worker loop written without Octane the cache survived the request that built it, and a membership revoked or granted elsewhere in the meantime was answered from the previous request's result. A revoked member could still be found as a member.

## [1.0.1] - 2026-06-26

### Changed

- Unify CI into a single workflow and test against Laravel 13.
- Add Dependabot, `.gitattributes` (`export-ignore`) and align project meta (CONTRIBUTING, SECURITY, LICENSE).

## [1.0.0] - 2026-05-18

### Added

- Backend-only polymorphic membership layer
- Single `memberships` table architecture
- `HasMembers` and `HasMemberships` traits
- Enum-based roles via `MembershipRole` contract
- Role hierarchy support with `RoleComparator`
- Actions: `AddMember`, `RemoveMember`, `UpdateMemberRole`
- Guards: `EnsureNotLastOwner`, `EnsureNotLastAdmin`, `EnsureRoleCanBeChanged`
- Events: `MemberAdded`, `MemberRemoved`, `MemberRoleUpdated`
- `MembershipGate` policy helper
- `FindMembership` query class
- Optional facade support
- Soft delete support
- Configurable `config/membership.php`
- Laravel 11, 12 and 13 support
- Pest test suite
- PHPStan level 6
- Laravel Pint formatting
- GitHub Actions CI workflows

[Unreleased]: https://github.com/vimatech-io/laravel-membership/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/vimatech-io/laravel-membership/compare/v1.0.2...v1.1.0
[1.0.2]: https://github.com/vimatech-io/laravel-membership/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/vimatech-io/laravel-membership/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/vimatech-io/laravel-membership/releases/tag/v1.0.0
