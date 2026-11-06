<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class StudioSettingsData extends Data
{
    public function __construct(public string $name, public string $contactEmail) {}
}
