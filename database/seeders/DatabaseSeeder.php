<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\SeedStarterSiteAction;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(SeedStarterSiteAction::class)->handle();
    }
}
