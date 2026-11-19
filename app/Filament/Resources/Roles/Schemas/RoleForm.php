<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Role Information')
                ->schema([
                    TextInput::make('name')
                        ->label('Role Name')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabled(
                            fn (?Role $record): bool =>
                                $record?->name === 'super_admin'
                        ),

                    TextInput::make('guard_name')
                        ->default('web')
                        ->required()
                        ->maxLength(255)
                        ->disabled(),
                ]),

            Section::make('Role Permissions')
                ->description(
                    'Select the permissions users assigned to this role should receive.'
                )
                ->schema([
                    CheckboxList::make('permissions')
                        ->relationship(
                            name: 'permissions',
                            titleAttribute: 'name'
                        )
                        ->label('Available Permissions')
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
