# Changelog

All notable changes to `vimatech/laravel-membership` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.2] - 2026-09-01

### Fixed

- The self-demotion and role-escalation guards no longer pass silently on a role they cannot rank. Both looked the level up themselves and skipped the check whenever either side was absent from `membership.roles`, so with the guard explicitly enabled a change to a custom role outside the map was permitted with nothing raised. They now go through `RoleComparator::isAtLeast()`, which was already the loud path and already threw `UnsupportedRoleHierarchyException` for exactly this case — the guards simply were not using it.

- The membership lookup cache is cleared when the application terminates instead of relying on `scoped()` bindings being dropped between requests. Laravel clears scoped bindings in one place only — between queue jobs — so under a worker loop written without Octane the cache survived the request that built it, and a membership revoked or granted elsewhere in the meantime was answered from the previous request's result. A revoked member could still be found as a member.

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

[Unreleased]: https://github.com/vimatech-io/laravel-membership/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/vimatech-io/laravel-membership/releases/tag/v1.0.0
