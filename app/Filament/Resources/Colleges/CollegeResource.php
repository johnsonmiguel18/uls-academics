<?php

namespace App\Filament\Resources\Colleges;

use App\Filament\Concerns\ScopedToCollege;
use App\Filament\Resources\Colleges\Pages\CreateCollege;
use App\Filament\Resources\Colleges\Pages\EditCollege;
use App\Filament\Resources\Colleges\Pages\ListColleges;
use App\Filament\Resources\Colleges\RelationManagers\DepartmentsRelationManager;
use App\Filament\Resources\Colleges\Schemas\CollegeForm;
use App\Filament\Resources\Colleges\Tables\CollegesTable;
use App\Models\College;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CollegeResource extends Resource
{
    use ScopedToCollege;

    protected static ?string $model = College::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|\UnitEnum|null $navigationGroup = 'Academics';

    protected static ?string $navigationLabel = 'Colleges & Units';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static function collegeKey(): string
    {
        return 'id';
    }

    public static function canCreate(): bool
    {
        return static::managesAllColleges();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::managesAllColleges();
    }

    public static function form(Schema $schema): Schema
    {
        return CollegeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollegesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DepartmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListColleges::route('/'),
            'create' => CreateCollege::route('/create'),
            'edit' => EditCollege::route('/{record}/edit'),
        ];
    }
}
