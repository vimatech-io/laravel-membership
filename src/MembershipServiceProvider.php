<?php

declare(strict_types=1);

namespace Vimatech\Membership;

use Illuminate\Support\ServiceProvider;
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
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/membership.php' => config_path('membership.php'),
            ], 'membership-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'membership-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
