<?php

declare(strict_types=1);

namespace Vimatech\Membership;

use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Vimatech\Membership\Actions\AddMember;
use Vimatech\Membership\Actions\EnsureNotLastAdmin;
use Vimatech\Membership\Actions\EnsureNotLastOwner;
use Vimatech\Membership\Actions\EnsureRoleCanBeChanged;
use Vimatech\Membership\Actions\RemoveMember;
use Vimatech\Membership\Actions\UpdateMemberRole;
use Vimatech\Membership\Queries\FindMembership;
use Vimatech\Membership\Support\RoleComparator;

final class MembershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/membership.php', 'membership');

        $this->app->scoped(RoleComparator::class);
        $this->app->scoped(FindMembership::class);
        $this->app->scoped(AddMember::class);
        $this->app->scoped(RemoveMember::class);
        $this->app->scoped(UpdateMemberRole::class);
        $this->app->scoped(EnsureNotLastOwner::class);
        $this->app->scoped(EnsureNotLastAdmin::class);
        $this->app->scoped(EnsureRoleCanBeChanged::class);
    }

    public function boot(): void
    {
        // Scoped bindings are dropped by the runner, not the framework: Laravel only
        // clears them between queue jobs. Registered once for the application.
        $this->app->terminating(function () {
            $this->app->make(FindMembership::class)->flush();
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/membership.php' => config_path('membership.php'),
            ], 'membership-config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_memberships_table.php' => database_path('migrations/0001_01_01_000003_create_memberships_table.php'),
            ], 'membership-migrations');
        }

        if ($this->runsMigrations()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    private function runsMigrations(): bool
    {
        $runMigrations = $this->app['config']->get('membership.run_migrations');

        if (! is_bool($runMigrations)) {
            throw new InvalidArgumentException(sprintf(
                'membership.run_migrations must be true or false, %s given. Set it to false only when your application ships the published memberships migration.',
                get_debug_type($runMigrations),
            ));
        }

        return $runMigrations;
    }
}
