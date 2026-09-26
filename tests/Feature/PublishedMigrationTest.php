<?php

declare(strict_types=1);

namespace Vimatech\Membership\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Vimatech\Membership\Tests\TestCase;

final class PublishedMigrationTest extends TestCase
{
    private string $databasePath;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $this->databasePath = sys_get_temp_dir().'/membership-'.bin2hex(random_bytes(6));
        (new Filesystem)->ensureDirectoryExists($this->databasePath.'/migrations');

        $app->useDatabasePath($this->databasePath);
        $app['config']->set('membership.run_migrations', false);
    }

    protected function defineDatabaseMigrations(): void {}

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->databasePath);

        parent::tearDown();
    }

    public function test_the_package_migration_is_not_registered_when_disabled(): void
    {
        $packageMigrations = realpath(__DIR__.'/../../database/migrations');

        $this->assertNotContains($packageMigrations, array_map('realpath', $this->app['migrator']->paths()));
    }

    public function test_an_application_migration_can_alter_and_reference_memberships_on_a_fresh_database(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'membership-migrations'])->assertSuccessful();

        copy(
            __DIR__.'/../Fixtures/migrations/2020_01_01_000000_extend_memberships_table.php',
            $this->databasePath.'/migrations/2020_01_01_000000_extend_memberships_table.php',
        );

        $this->artisan('migrate')->assertSuccessful();

        $this->assertTrue(Schema::hasColumn('memberships', 'seat'));
        $this->assertTrue(Schema::hasTable('membership_notes'));
    }
}
