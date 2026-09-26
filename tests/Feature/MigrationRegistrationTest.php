<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Vimatech\Membership\MembershipServiceProvider;

it('keeps loading the migration under the name existing databases recorded', function () {
    $packageMigrations = realpath(__DIR__.'/../../database/migrations');

    expect(array_map('realpath', app('migrator')->paths()))->toContain($packageMigrations)
        ->and(array_keys(app('migrator')->getMigrationFiles([$packageMigrations])))->toBe(['create_memberships_table']);
});

it('publishes the migration under a name that sorts before application migrations', function () {
    $published = ServiceProvider::pathsToPublish(MembershipServiceProvider::class, 'membership-migrations');

    expect(array_map('basename', array_values($published)))->toBe(['0001_01_01_000003_create_memberships_table.php']);
});

it('refuses a run_migrations value that is not a boolean', function () {
    config()->set('membership.run_migrations', 'false');

    (new MembershipServiceProvider(app()))->boot();
})->throws(InvalidArgumentException::class, 'membership.run_migrations must be true or false, string given');
