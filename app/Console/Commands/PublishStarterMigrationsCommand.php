<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\PublishStarterMigrationsAction;
use Capell\Core\Support\Install\ConsoleProgressReporter;
use Illuminate\Console\Command;

final class PublishStarterMigrationsCommand extends Command
{
    protected $signature = 'starter:publish-migrations';

    protected $description = 'Publish migrations from the installed, locked Capell release';

    public function handle(PublishStarterMigrationsAction $publish): int
    {
        $publish->handle(new ConsoleProgressReporter($this));

        return self::SUCCESS;
    }
}
