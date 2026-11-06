<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Actions\SaveStudioSettingsAction;
use App\Data\StudioSettingsData;
use App\Models\StudioSetting;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;
use Override;

final class StudioSettingsWidget extends Widget
{
    protected static ?bool $isLazyLoaded = false;

    protected string $view = 'filament.widgets.studio-settings';

    public string $studioName = '';

    public string $contactEmail = '';

    #[Override]
    public static function canView(): bool
    {
        return Gate::allows('manage-studio');
    }

    public function mount(): void
    {
        Gate::authorize('manage-studio');
        $settings = StudioSetting::query()->firstOrFail();
        $this->studioName = $settings->name;
        $this->contactEmail = $settings->contact_email;
    }

    public function save(SaveStudioSettingsAction $save): void
    {
        $save->handle(new StudioSettingsData($this->studioName, $this->contactEmail));
        Notification::make()->title(__('starter.settings_saved'))->success()->send();
    }
}
