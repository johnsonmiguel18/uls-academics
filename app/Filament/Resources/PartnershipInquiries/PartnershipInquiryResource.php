<?php

namespace App\Filament\Resources\PartnershipInquiries;

use App\Filament\Resources\PartnershipInquiries\Pages\ManagePartnershipInquiries;
use App\Models\PartnershipInquiry;
use App\Support\Site;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Admin → Global engagement → Inquiries: messages from the Global site's "Let's connect" form. */
class PartnershipInquiryResource extends Resource
{
    protected static ?string $model = PartnershipInquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Global engagement';

    protected static ?string $navigationLabel = 'Inquiries';

    protected static ?string $modelLabel = 'inquiry';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage_academics') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $new = PartnershipInquiry::where('status', 'new')->count();

        return $new ? (string) $new : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->disabled(),
            TextInput::make('email')->disabled(),
            TextInput::make('organization')->disabled(),
            Select::make('interest')->options(fn () => Site::get('global.interests', []))->disabled(),
            Textarea::make('message')->rows(6)->disabled()->columnSpanFull(),
            Select::make('status')->options(PartnershipInquiry::STATUSES)->required(),
            Textarea::make('notes')->label('Internal notes')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
                TextColumn::make('name')->searchable()->description(fn (PartnershipInquiry $r) => $r->organization),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('interest')->formatStateUsing(fn (?string $state) => Site::get("global.interests.$state", $state))->toggleable(),
                TextColumn::make('message')->limit(60)->wrap()->toggleable(),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state) => PartnershipInquiry::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) { 'new' => 'warning', 'in_progress' => 'info', 'answered' => 'success', default => 'gray' }),
            ])
            ->filters([SelectFilter::make('status')->options(PartnershipInquiry::STATUSES)])
            ->recordActions([
                Action::make('reply')->label('Reply')->icon(Heroicon::OutlinedEnvelope)
                    ->url(fn (PartnershipInquiry $r) => 'mailto:' . $r->email . '?subject=' . rawurlencode('Re: your inquiry to ' . Site::text('global.brand_label')))
                    ->openUrlInNewTab(),
                EditAction::make()->label('Open'),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePartnershipInquiries::route('/')];
    }
}
