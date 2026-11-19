<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
{
    return 'System Administration';
}

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage_users') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage_users') ?? false;
    }

    public static function canEdit(
        \Illuminate\Database\Eloquent\Model $record
    ): bool {
        $user = auth()->user();

        if (! ($user?->can('manage_users') ?? false)) {
            return false;
        }

        // Prevent editing Super Admin accounts through this resource.
        return ! $record->hasRole('super_admin');
    }

    public static function canDelete(
        \Illuminate\Database\Eloquent\Model $record
    ): bool {
        $user = auth()->user();

        if (! ($user?->can('manage_users') ?? false)) {
            return false;
        }

        // Never delete a Super Admin or the currently logged-in account.
        return ! $record->hasRole('super_admin')
            && (int) $record->getKey() !== (int) $user->getKey();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}

