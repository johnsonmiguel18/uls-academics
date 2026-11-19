<?php

namespace App\Filament\Resources\Permissions\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PermissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Permission')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge(),

                TextColumn::make('roles_count')
                    ->label('Assigned Roles')
                    ->counts('roles')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
               EditAction::make()
    ->visible(
        fn ($record): bool => ! in_array(
            $record->name,
            [
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
            ],
            true
        )
    ),

                DeleteAction::make()
                    ->visible(
                        fn ($record): bool => ! in_array(
                            $record->name,
                            [
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
                            ],
                            true
                        )
                    )
                    ->requiresConfirmation(),
            ]);
    }
}
