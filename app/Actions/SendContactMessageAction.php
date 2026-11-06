<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ContactData;
use App\Mail\ContactMessage;
use App\Models\StudioSetting;
use Illuminate\Support\Facades\Mail;

final class SendContactMessageAction
{
    public function handle(ContactData $data): void
    {
        Mail::to(StudioSetting::query()->firstOrFail()->contact_email)->send(new ContactMessage($data));
    }
}
