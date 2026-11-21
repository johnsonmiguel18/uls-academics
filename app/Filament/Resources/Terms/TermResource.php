<?php

namespace App\Filament\Resources\Terms;

use App\Filament\Resources\Terms\Pages\ManageTerms;
use App\Models\Term;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Admin → Lists and categories: academic levels, degree types, news and document
 * categories, and the SDGs. Every dropdown and label on the site comes from here.
 */
class TermResource extends Resource
{
    protected static ?string $model = Term::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    protected static ?string $navigationLabel = 'Lists and categories';

    protected static ?string $modelLabel = 'list entry';

    protected static ?string $pluralModelLabel = 'Lists and categories';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage_academics') ?? false;
    }

    /** Entries still used by programs, colleges, news or documents can be hidden, not deleted. */
    public static function canDelete(Model $record): bool
    {
        return static::canViewAny() && ! $record->isInUse();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('List')
                ->options(Term::TYPES)
                ->required()
                ->live()
                ->disabledOn('edit'),
            TextInput::make('label')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $get, string $operation) => $operation === 'create' && blank($get('key')) ? $set('key', Str::slug($state)) : null),
            TextInput::make('key')
                ->required()
                ->maxLength(80)
                ->alphaDash()
                ->disabledOn('edit')
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, Get $get) => $rule->where('type', $get('type')))
                ->helperText('Saved in records and used in links (e.g. ?level=graduate). Cannot change later; rename the label instead.'),
            ColorPicker::make('color')
                ->visible(fn (Get $get) => $get('type') === 'sdg')
                ->helperText('Badge color. Text color and the pale "no stories yet" tint are worked out from it, readable at WCAG AA.'),
            \Filament\Forms\Components\Textarea::make('description')
                ->visible(fn (Get $get) => $get('type') === 'sdg')
                ->rows(3)->maxLength(1000)
                ->helperText("Introduction on this goal's own page (/sdgs/number)."),
            TextInput::make('sort_order')->numeric()->default(0)->helperText('Lower numbers come first in menus and filters.'),
            Toggle::make('is_active')->label('Shown on the site and in dropdowns')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('List')->formatStateUsing(fn ($state) => Term::TYPES[$state] ?? $state)->sortable(),
                ColorColumn::make('color')->disabledClick()->label('')->visibleFrom('md'),
                TextColumn::make('label')->searchable()->weight('bold'),
                TextColumn::make('key')->color('gray')->visibleFrom('md'),
                TextColumn::make('sort_order')->label('Order')->sortable()->visibleFrom('lg'),
                IconColumn::make('is_active')->label('Shown')->boolean(),
            ])
            ->filters([SelectFilter::make('type')->label('List')->options(Term::TYPES)])
            ->defaultSort(fn ($query) => $query->orderBy('type')->orderBy('sort_order'))
            ->defaultGroup('type')
            ->groups([\Filament\Tables\Grouping\Group::make('type')->label('List')->getTitleFromRecordUsing(fn (Term $t) => Term::TYPES[$t->type] ?? $t->type)])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (Term $record) => static::canDelete($record)),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTerms::route('/'),
        ];
    }
}
