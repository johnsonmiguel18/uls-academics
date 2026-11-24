<?php

namespace App\Filament\Forms;

use App\Models\Term;
use App\Support\Sdg;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

/**
 * One partner: used for each college's partners (college page) and for the university-wide
 * partners (Site settings → Global engagement). The Global Engagement site lists both.
 */
class PartnerFields
{
    public static function repeater(string $name, string $label): Repeater
    {
        return Repeater::make($name)
            ->label($label)
            ->schema([
                TextInput::make('name')->required(),
                \Filament\Forms\Components\FileUpload::make('logo')->label('Logo')
                    ->image()->disk('public')->directory('partners')->visibility('public')->maxSize(2048)
                    ->imagePreviewHeight('80')->helperText('Square or round logo, at least 200 px. Optional.'),
                TextInput::make('type')->placeholder('Government partner, Partner school, Industry...'),
                Select::make('scope')->label('Local or international')
                    ->options(fn () => Term::options('partner_scope'))->default(fn () => array_key_first(Term::options('partner_scope'))),
                TextInput::make('country')->placeholder('Philippines, Japan...'),
                TextInput::make('period')->placeholder('Since 2023, 2025–2026, Ongoing...'),
                Select::make('sdgs')->label('SDGs')->options(fn () => Sdg::options())->multiple(),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                Repeater::make('links')
                    ->schema([TextInput::make('label')->required(), TextInput::make('url')->url()->required()])
                    ->columns(2)->defaultItems(0)->columnSpanFull()->addActionLabel('Add link'),
            ])
            ->columns(2)
            ->itemLabel(fn (array $state) => $state['name'] ?? null)
            ->collapsible()->collapsed()->defaultItems(0)->addActionLabel('Add partner');
    }
}
