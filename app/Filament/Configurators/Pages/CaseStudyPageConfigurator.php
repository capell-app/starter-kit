<?php

declare(strict_types=1);

namespace App\Filament\Configurators\Pages;

use Capell\Admin\Filament\Configurators\Pages\DefaultPageConfigurator;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Override;

final class CaseStudyPageConfigurator extends DefaultPageConfigurator
{
    #[Override]
    protected function getEditFormSchema(Schema $schema): array
    {
        return [
            ...parent::getEditFormSchema($schema),
            Section::make(__('starter.case_study'))->schema([
                TextInput::make('meta.client')->label(__('starter.client'))->required()->maxLength(100),
                TextInput::make('meta.year')->label(__('starter.year'))->required()->regex('/^[0-9]{4}$/'),
            ]),
        ];
    }
}
