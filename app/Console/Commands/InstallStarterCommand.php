<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\CompleteStarterInstallationAction;
use App\Actions\CreateStarterAdministratorAction;
use App\Actions\PublishStarterMigrationsAction;
use App\Actions\SeedStarterSiteAction;
use Capell\Core\Support\Install\ConsoleProgressReporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class InstallStarterCommand extends Command
{
    protected $signature = 'starter:install {--admin-email= : Required for production bootstrap}';

    protected $description = 'Prepare the local Capell studio without resetting existing data';

    public function handle(SeedStarterSiteAction $seed): int
    {
        $email = $this->option('admin-email') ?? 'admin@example.test';
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || (app()->environment('production') && (! $this->option('admin-email') || str_ends_with($email, '.test')))) {
            $this->error('Production bootstrap requires --admin-email with a real email address.');

            return self::FAILURE;
        }
        if (blank(config('app.key')) && $this->call('key:generate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }
        if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') !== ':memory:') {
            $database = config('database.connections.sqlite.database');
            if (! is_file($database)) {
                touch($database);
            }
        }
        // Use the released migration publishers, with no package selection or source patches.
        $reporter = new ConsoleProgressReporter($this);
        app(PublishStarterMigrationsAction::class)->handle($reporter);
        foreach ([
            ['migrate', ['--force' => true]],
            ['migrate', ['--path' => 'database/settings', '--force' => true]],
            ['capell:admin-install', ['--admin-panel-changes' => 'manual']],
            ['filament:assets', []],
            ['capell:package-cache', []],
        ] as [$command, $arguments]) {
            if ($this->call($command, $arguments) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }
        $created = DB::transaction(function () use ($email, $seed): bool {
            $created = app(CreateStarterAdministratorAction::class)->handle($email);
            $seed->handle();
            app(CompleteStarterInstallationAction::class)->handle();

            return $created;
        });
        if ($created) {
            $this->info('Admin account: '.$email);
            $this->line('Generated password saved privately to storage/app/private/starter-admin-password. Store it securely, then delete that file.');
        }
        $this->info('Studio ready. Run php artisan serve, then visit / and /admin.');

        return self::SUCCESS;
    }
}
