<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Full Name')
                    ->weight('bold')
                    ->description(fn ($record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(',')
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->placeholder('No role assigned'),

                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),

                DeleteAction::make()
                    ->visible(function ($record): bool {
                        $user = auth()->user();

                        return ($user?->can('manage_users') ?? false)
                            && ! $record->hasRole('super_admin')
                            && (int) $record->getKey() !== (int) $user?->getKey();
                    })
                    ->authorize(function ($record): bool {
                        $user = auth()->user();

                        return ($user?->can('manage_users') ?? false)
                            && ! $record->hasRole('super_admin')
                            && (int) $record->getKey() !== (int) $user?->getKey();
                    })
                    ->requiresConfirmation(),
            ]);
    }
}
