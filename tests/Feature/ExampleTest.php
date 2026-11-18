<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

// The Laravel installer's --pest step writes a stock ExampleTest that expects
// 200 at "/" whenever this file is missing. A fresh project has no seeded site
// in the test database, so that stock test returns 404. Keeping a real home
// page test under this name stops the installer replacing it.
it('serves the seeded home page with navigation to the contact page', function (): void {
    Storage::fake('local');
    Artisan::call('starter:install', ['--no-interaction' => true]);

    $this->get('/')->assertOk()->assertSee('href="/contact"', false);
});
