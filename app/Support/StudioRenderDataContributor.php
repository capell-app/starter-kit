<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\StudioSetting;
use Capell\Core\Models\Translation;
use Capell\Frontend\Contracts\PublicRenderDataContributor;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Data\PublicRenderDataCacheDependencyData;
use Capell\Frontend\Data\PublicRenderDataContributionData;
use Capell\Frontend\Data\PublicRenderDataContributionMetadataData;
use Override;

final class StudioRenderDataContributor implements PublicRenderDataContributor
{
    #[Override]
    public function key(): string
    {
        return 'studio';
    }

    #[Override]
    public function supports(FrontendRenderContextData $context): bool
    {
        return $context->page !== null;
    }

    #[Override]
    public function cacheDependencyModelTypes(): array
    {
        return [StudioSetting::class, Translation::class];
    }

    #[Override]
    public function metadata(FrontendRenderContextData $context): PublicRenderDataContributionMetadataData
    {
        $settings = StudioSetting::query()->firstOrFail();
        $translation = $context->page->translations()->where('language_id', $context->language->id)->firstOrFail();

        return new PublicRenderDataContributionMetadataData(
            fingerprint: hash('sha256', $settings->toJson().$translation->toJson()),
            cacheDependencies: [PublicRenderDataCacheDependencyData::forModel($settings), PublicRenderDataCacheDependencyData::forModel($translation)],
        );
    }

    #[Override]
    public function contribute(FrontendRenderContextData $context): PublicRenderDataContributionData
    {
        $settings = StudioSetting::query()->firstOrFail();
        $translation = $context->page->translations()->where('language_id', $context->language->id)->firstOrFail();

        return new PublicRenderDataContributionData((object) [
            'client' => $context->page->meta['client'] ?? '',
            'year' => $context->page->meta['year'] ?? '',
            'name' => $settings->name,
            'title' => $translation->title,
            'summary' => $translation->meta['summary'] ?? '',
            'kind' => $context->page->blueprint->key,
        ]);
    }
}
