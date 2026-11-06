<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\StudioSetting;
use Capell\Core\Actions\CreateDefaultLanguageAction;
use Capell\Core\Actions\CreateSiteAction;
use Capell\Core\Enums\BlueprintSubjectEnum;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Creator\BlueprintCreator;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class SeedStarterSiteAction
{
    public function handle(): void
    {
        DB::transaction(function (): void {
            $settings = StudioSetting::query()->firstOrCreate(['id' => 1], ['name' => 'North Studio', 'contact_email' => 'hello@example.test']);
            app(BlueprintCreator::class)->createPageTypes();
            $language = CreateDefaultLanguageAction::run();
            $theme = Theme::query()->firstOrCreate(['key' => 'studio'], [
                'name' => 'Studio', 'blueprint_id' => app(BlueprintCreator::class)->createThemeType()->id,
                'default' => true, 'status' => true,
            ]);
            $site = $settings->site_id !== null ? Site::query()->findOrFail($settings->site_id) : CreateSiteAction::run('Studio', config('app.url'), $language, theme: $theme);
            if ($settings->site_id === null) {
                $settings->update(['site_id' => $site->id]);
            }
            $layout = Layout::query()->firstOrCreate(['key' => 'studio'], [
                'name' => 'Studio', 'theme_id' => $theme->id, 'site_id' => $site->id,
                'default' => true, 'status' => true,
                'meta' => ['layout_file' => 'capell.themes.capell-app.studio.layout', 'master_file' => 'capell.themes.capell-app.studio.empty'],
            ]);
            if (! Layout::query()->where('default', true)->exists()) {
                $layout->update(['default' => true]);
            }
            foreach ([
                ['home', 'Home', '/', 'home_title', 'home_summary'],
                ['case-study', 'Field Notes', '/work/field-notes', 'case_title', 'case_summary'],
                ['contact', 'Contact', '/contact', 'contact_title', 'contact_summary'],
            ] as [$key, $name, $url, $title, $summary]) {
                $blueprint = Blueprint::query()->firstOrCreate(['key' => $key, 'type' => BlueprintSubjectEnum::Page], [
                    'name' => $name, 'default' => false,
                    'admin' => ['configurator' => $key === 'case-study' ? 'CaseStudy' : 'Default'],
                    'meta' => ['rendering_strategy' => 'blade'],
                ]);
                $uuid = Uuid::uuid5(Uuid::NAMESPACE_URL, 'https://github.com/capell-app/starter-kit/'.$key)->toString();
                $page = Page::query()->firstOrCreate(['uuid' => $uuid], [
                    'name' => $name, 'site_id' => $site->id,
                    'blueprint_id' => $blueprint->id, 'layout_id' => $layout->id,
                    'meta' => $key === 'case-study' ? ['client' => 'Field Notes', 'year' => '2026'] : [],
                ]);
                if ($page->wasRecentlyCreated) {
                    $page->update(['layout_id' => $layout->id, 'meta' => [...($page->meta ?? []), 'studio_seeded' => true]]);
                    $page->translations()->updateOrCreate(['language_id' => $language->id], [
                        'title' => __('starter.'.$title), 'meta' => ['summary' => __('starter.'.$summary), 'slug' => trim($url, '/')],
                    ]);
                    $existingUrl = PageUrl::query()->where('site_id', $site->id)->where('language_id', $language->id)->where('url', $url)->first();
                    throw_if($existingUrl !== null && (string) $existingUrl->pageable_id !== (string) $page->id, \RuntimeException::class, 'A starter URL is already owned by another page.');
                    PageUrl::query()->updateOrCreate(['site_id' => $site->id, 'language_id' => $language->id, 'url' => $url], [
                        'pageable_type' => $page->getMorphClass(), 'pageable_id' => $page->id, 'status' => true, 'is_manual' => true,
                    ]);
                }
            }
        });
    }
}
