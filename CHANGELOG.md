# Changelog

All notable changes to `laravel-membership` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
