<?php

declare(strict_types=1);

use App\Actions\CompleteStarterInstallationAction;
use App\Actions\CreateStarterAdministratorAction;
use App\Actions\SaveStudioSettingsAction;
use App\Actions\SeedStarterSiteAction;
use App\Data\StudioSettingsData;
use App\Filament\Widgets\StudioSettingsWidget;
use App\Mail\ContactMessage;
use App\Models\StudioSetting;
use App\Models\User;
use Capell\Admin\Filament\Pages\CapellDashboard;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Core\Actions\Diagnostics\ResolveCapellInstallationStateAction;
use Capell\Core\Enums\Diagnostics\CapellInstallationState;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Runtime\RuntimeRolePackageManifest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('local');
    Artisan::call('starter:install', ['--no-interaction' => true]);
});

it('renders CMS pages through the local theme with no authoring state', function (string $url): void {
    $response = $this->get($url)->assertOk();
    $dom = new DOMDocument;
    @$dom->loadHTML($response->getContent());
    $xpath = new DOMXPath($dom);
    expect($xpath->query('//main')->length)->toBe(1)
        ->and($xpath->query('//h1')->length)->toBe(1)
        ->and($xpath->query('//link[@rel="stylesheet" and contains(@href,"starter.css")]')->length)->toBe(1);
    $response->assertDontSee('wire:id', false)->assertDontSee('blueprint_id')->assertDontSee('data-capell');
})->with(['/', '/work/field-notes', '/contact']);

it('seeds a custom case study type without duplicating editorial content', function (): void {
    $before = Page::count();
    app(SeedStarterSiteAction::class)->handle();
    expect(Page::count())->toBe($before)
        ->and(Blueprint::query()->where('key', 'case-study')->where('type', 'page')->exists())->toBeTrue();
});

it('shows the styled Admin login and protects the dashboard', function (): void {
    $this->get('/admin/login')->assertOk()->assertSee('filament', false);
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('lets an administrator save settings and updates the public site', function (): void {
    $admin = User::query()->where('email', 'admin@example.test')->firstOrFail();
    $this->actingAs($admin)->get('/admin')->assertOk();
    Livewire::test(StudioSettingsWidget::class)->set('studioName', 'Changed studio')->call('save')->assertHasNoErrors();
    expect(StudioSetting::query()->firstOrFail()->name)->toBe('Changed studio');
    $this->get('/')->assertSee('Changed studio');
});

it('refuses settings changes by an ordinary user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin')->assertForbidden();
    Livewire::test(StudioSettingsWidget::class)->assertForbidden();
    expect(fn () => app(SaveStudioSettingsAction::class)->handle(new StudioSettingsData('Not allowed', 'visitor@example.test')))->toThrow(AuthorizationException::class);
});

it('validates settings before saving', function (): void {
    $this->actingAs(User::query()->where('email', 'admin@example.test')->firstOrFail());
    Livewire::test(StudioSettingsWidget::class)->set('studioName', '')->call('save')->assertHasErrors(['studioName' => 'required']);
});

it('delivers a validated contact message synchronously', function (): void {
    Mail::fake();
    $this->from('/contact')->post('/contact', ['name' => 'Visitor', 'email' => 'visitor@example.test', 'message' => 'Please tell me more about your work.'])
        ->assertRedirect('/contact')->assertSessionHas('status');
    Mail::assertSent(ContactMessage::class, fn (ContactMessage $mail): bool => $mail->hasTo('hello@example.test') && $mail->hasReplyTo('visitor@example.test'));
});

it('rejects invalid contact input without sending mail', function (): void {
    Mail::fake();
    $this->post('/contact', ['name' => '', 'email' => 'invalid', 'message' => ''])->assertSessionHasErrors(['name', 'email', 'message']);
    Mail::assertNothingSent();
});

it('rate limits contact submissions', function (): void {
    Mail::fake();
    foreach (range(1, 5) as $attempt) {
        $this->post('/contact', [])->assertSessionHasErrors();
    }
    $this->post('/contact', [])->assertTooManyRequests();
});

it('edits the custom page fields in Admin and renders them publicly', function (): void {
    $this->actingAs(User::query()->where('email', 'admin@example.test')->firstOrFail());
    $page = Page::query()->whereHas('blueprint', fn ($query) => $query->where('key', 'case-study'))->firstOrFail();
    Livewire::test(EditPage::class, ['record' => (string) $page->id])
        ->assertFormFieldExists('meta.client')
        ->fillForm(['meta' => [...$page->meta, 'client' => 'Edited client', 'year' => '2027']])
        ->call('save')->assertHasNoFormErrors();
    $this->get('/work/field-notes')->assertOk()->assertSee('Edited client')->assertSee('2027');
});

it('preserves edited content when setup is repeated', function (): void {
    $page = Page::query()->where('name', 'Home')->firstOrFail();
    $page->translations()->firstOrFail()->update(['title' => 'An edited title']);
    app(SeedStarterSiteAction::class)->handle();
    expect($page->translations()->firstOrFail()->title)->toBe('An edited title');
});

it('escapes editorial data in the public theme', function (): void {
    StudioSetting::query()->firstOrFail()->update(['name' => '<script>alert(1)</script>']);
    $this->get('/')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

it('routes enquiries to the recipient saved in the settings widget', function (): void {
    $this->actingAs(User::query()->where('email', 'admin@example.test')->firstOrFail());
    Livewire::test(StudioSettingsWidget::class)->set('contactEmail', 'studio@example.test')->call('save')->assertHasNoErrors();
    Mail::fake();
    $this->post('/contact', ['name' => 'Visitor', 'email' => 'visitor@example.test', 'message' => 'A real enquiry for the studio.'])->assertRedirect('/contact');
    Mail::assertSent(ContactMessage::class, fn (ContactMessage $mail): bool => $mail->hasTo('studio@example.test'));
});

it('refuses malformed recipient settings', function (): void {
    $this->actingAs(User::query()->where('email', 'admin@example.test')->firstOrFail());
    Livewire::test(StudioSettingsWidget::class)->set('contactEmail', 'invalid')->call('save')->assertHasErrors(['contactEmail' => 'email']);
});

it('preserves the application key and encrypted data when setup repeats', function (): void {
    $key = config('app.key');
    $encrypted = Crypt::encryptString('retained data');
    $this->artisan('starter:install')->assertSuccessful();
    expect(config('app.key'))->toBe($key)
        ->and(Crypt::decryptString($encrypted))->toBe('retained data');
});

it('preserves renamed pages and withdrawn routes when setup repeats', function (): void {
    $page = Page::query()->where('name', 'Home')->firstOrFail();
    $page->update(['name' => 'Renamed home']);
    $url = $page->pageUrls()->where('url', '/')->firstOrFail();
    $url->update(['status' => false]);
    $count = Page::count();
    $this->artisan('starter:install')->assertSuccessful();
    expect(Page::count())->toBe($count)->and($url->fresh()->status)->toBeFalse()->and($page->fresh()->name)->toBe('Renamed home');
});

it('preserves the starter site identity after an editor renames it', function (): void {
    $settings = StudioSetting::query()->firstOrFail();
    $site = Site::query()->findOrFail($settings->site_id);
    $site->update(['name' => 'Renamed studio']);
    $count = Site::count();
    $this->artisan('starter:install')->assertSuccessful();
    expect(Site::count())->toBe($count)->and($settings->fresh()->site_id)->toBe($site->id);
});

it('does not create an inaccessible administrator after a password write failure', function (): void {
    $manager = Mockery::mock(FilesystemManager::class);
    $disk = Mockery::mock(FilesystemAdapter::class);
    $manager->shouldReceive('disk')->with('local')->andReturn($disk);
    $disk->shouldReceive('put')->once()->andReturn(false);
    expect(fn () => (new CreateStarterAdministratorAction($manager))->handle('new@example.test'))->toThrow(RuntimeException::class);
    expect(User::query()->where('email', 'new@example.test')->exists())->toBeFalse();
    expect(app(CreateStarterAdministratorAction::class)->handle('new@example.test'))->toBeTrue();
    $password = Storage::disk('local')->get('starter-admin-password');
    expect(Hash::check($password, User::query()->where('email', 'new@example.test')->firstOrFail()->password))->toBeTrue();
});

it('rolls back a colliding example and can recover after the URL is released', function (): void {
    $original = Page::query()->where('name', 'Home')->firstOrFail();
    $original->update(['uuid' => Str::uuid()->toString()]);
    $route = $original->pageUrls()->where('url', '/')->firstOrFail();
    $count = Page::count();
    expect(fn () => app(SeedStarterSiteAction::class)->handle())->toThrow(RuntimeException::class);
    expect(Page::count())->toBe($count);
    $route->update(['url' => '/previous-home']);
    app(SeedStarterSiteAction::class)->handle();
    expect(Page::count())->toBe($count + 1);
    $this->get('/')->assertOk();
});

it('shows the settings widget on the actual Capell dashboard', function (): void {
    $this->actingAs(User::query()->where('email', 'admin@example.test')->firstOrFail());
    Livewire::test(CapellDashboard::class)->assertSeeLivewire(StudioSettingsWidget::class);
});

it('provides default design records for newly authored pages', function (): void {
    expect(Theme::query()->where('default', true)->firstOrFail()->key)->toBe('studio')
        ->and(Layout::query()->where('default', true)->firstOrFail()->key)->toBe('studio');
});

it('records a completed Core installation', function (): void {
    expect(ResolveCapellInstallationStateAction::run())->toBe(CapellInstallationState::Installed);
});

it('boots the released Capell runtime contract', function (): void {
    expect(app(PackageManifest::class))->toBeInstanceOf(RuntimeRolePackageManifest::class)
        ->and(app()->getCachedConfigPath())->toContain('/capell-runtime/combined/');
});

it('passes the released health check after bootstrap', function (): void {
    $this->artisan('capell:doctor')->assertSuccessful();
});

it('preserves edited custom CSS while creating missing starter support CSS', function (): void {
    $originalBase = base_path();
    $temporary = storage_path('framework/testing/starter-assets-'.Str::uuid()->toString());
    File::ensureDirectoryExists($temporary.'/resources/views');
    $custom = $temporary.'/authored.css';
    $edited = 'body { color: rebeccapurple; }';
    file_put_contents($custom, $edited);
    app()->setBasePath($temporary);
    config(['capell-frontend.tailwind.output_css' => $custom]);
    try {
        app(CompleteStarterInstallationAction::class)->handle();
        expect(file_get_contents($custom))->toBe($edited)
            ->and(is_file($temporary.'/resources/css/capell/frontend.css'))->toBeTrue();
    } finally {
        app()->setBasePath($originalBase);
        File::deleteDirectory($temporary);
    }
});
