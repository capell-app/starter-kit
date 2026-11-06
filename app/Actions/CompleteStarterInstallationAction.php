<?php

declare(strict_types=1);

namespace App\Actions;

use Capell\Core\Enums\ExtensionStatusEnum;
use Capell\Core\Events\CapellInstallationCompleted;
use Capell\Core\Models\CapellExtension;
use Capell\Frontend\Actions\GenerateTailwindAssetsAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class CompleteStarterInstallationAction
{
    public function handle(): void
    {
        if (! is_file(resource_path('css/capell/frontend.css'))) {
            GenerateTailwindAssetsAction::run();
        }
        $core = CapellExtension::query()->firstOrNew(['composer_name' => 'capell-app/core']);
        if ($core->installed_at !== null) {
            return;
        }
        $core->fill(['name' => 'Capell Core', 'status' => ExtensionStatusEnum::Enabled, 'installed_at' => now(), 'is_paid_marketplace_extension' => false])->save();
        DB::afterCommit(static function (): void {
            Event::dispatch(new CapellInstallationCompleted);
        });
    }
}
