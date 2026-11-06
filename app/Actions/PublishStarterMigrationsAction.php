<?php

declare(strict_types=1);

namespace App\Actions;

use Capell\Core\Actions\Install\PublishCapellMigrationsAction;
use Capell\Core\Actions\Install\PublishPackageMigrationsAction;
use Capell\Core\Actions\Install\PublishVendorMigrationsAction;
use Capell\Core\Contracts\ProgressReporter;
use Capell\Core\Facades\CapellCore;

final class PublishStarterMigrationsAction
{
    public function handle(ProgressReporter $reporter): void
    {
        PublishVendorMigrationsAction::run($reporter);
        PublishCapellMigrationsAction::run($reporter);
        PublishPackageMigrationsAction::run(CapellCore::getPackages(), $reporter);
    }
}
