<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\StudioSettingsData;
use App\Models\StudioSetting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class SaveStudioSettingsAction
{
    public function handle(StudioSettingsData $settings): void
    {
        Gate::authorize('manage-studio');
        $data = Validator::make(['studioName' => $settings->name, 'contactEmail' => $settings->contactEmail], ['studioName' => ['required', 'string', 'max:100'], 'contactEmail' => ['required', 'email', 'max:254']])->validate();
        StudioSetting::query()->firstOrFail()->update(['name' => $data['studioName'], 'contact_email' => $data['contactEmail']]);
    }
}
