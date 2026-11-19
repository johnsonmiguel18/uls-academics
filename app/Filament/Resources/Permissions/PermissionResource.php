<?php

namespace App\Filament\Resources\Permissions;

use App\Filament\Resources\Permissions\Pages\CreatePermission;
use App\Filament\Resources\Permissions\Pages\EditPermission;
use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Filament\Resources\Permissions\Schemas\PermissionForm;
use App\Filament\Resources\Permissions\Tables\PermissionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Permissions';

    protected static string|\UnitEnum|null $navigationGroup =
        'System Administration';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    protected static function isCorePermission(string $name): bool
{
    return in_array($name, [
        'view_pages',
        'create_pages',
        'edit_pages',
        'delete_pages',
        'publish_pages',
        'manage_protected_pages',
        'view_news',
        'create_news',
        'edit_news',
        'delete_news',
        'publish_news',
        'manage_users',
        'manage_roles',
        'manage_settings',
    ], true);
}

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canEdit(Model $record): bool
{
    return (auth()->user()?->hasRole('super_admin') ?? false)
        && ! static::isCorePermission($record->name);
}

    public static function canDelete(Model $record): bool
    {
        return (auth()->user()?->hasRole('super_admin') ?? false)
            && ! in_array($record->name, [
                'view_pages',
                'create_pages',
                'edit_pages',
                'delete_pages',
                'publish_pages',
                'manage_protected_pages',
                'view_news',
                'create_news',
                'edit_news',
                'delete_news',
                'publish_news',
                'manage_users',
                'manage_roles',
                'manage_settings',
            ], true);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return PermissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            'create' => CreatePermission::route('/create'),
            'edit' => EditPermission::route('/{record}/edit'),
        ];
    }
}
