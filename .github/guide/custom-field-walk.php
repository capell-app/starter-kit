<?php

declare(strict_types=1);

use App\Models\User;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Core\Models\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('local');
    Artisan::call('starter:install', ['--no-interaction' => true]);
});

it('shows a client name saved from the custom field guide on the anonymous page', function (): void {
    $administrator = User::query()->where('email', 'admin@example.test')->firstOrFail();
    $caseStudy = Page::query()->whereHas('blueprint', fn ($query) => $query->where('key', 'case-study'))->firstOrFail();
    $contact = Page::query()->where('name', 'Contact')->firstOrFail();

    $this->actingAs($administrator);
    Livewire::test(EditPage::class, ['record' => (string) $caseStudy->id])
        ->assertFormFieldExists('meta.case_study.client_name')
        ->fillForm(['meta' => [...$caseStudy->meta, 'case_study' => ['client_name' => 'Field Notes Studio']]])
        ->call('save')
        ->assertHasNoFormErrors();
    Livewire::test(EditPage::class, ['record' => (string) $contact->id])
        ->assertFormFieldDoesNotExist('meta.case_study.client_name');
    auth()->logout();

    $this->get('/work/field-notes')->assertOk()->assertSee('Field Notes Studio');
});
