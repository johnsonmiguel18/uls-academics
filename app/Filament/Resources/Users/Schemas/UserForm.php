<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User Information')
                ->schema([
                    TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool =>
                            $operation === 'create'
                        )
                        ->dehydrated(fn (?string $state): bool =>
                            filled($state)
                        )
                        ->dehydrateStateUsing(
                            fn (string $state): string => Hash::make($state)
                        )
                        ->minLength(12)
                        ->maxLength(255)
                        ->helperText(
                            'Required for new users. Leave blank when editing to keep the current password.'
                        ),

                    Select::make('roles')
                        ->label('User Role')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->required()
                        ->visible(fn (): bool =>
                            auth()->user()?->hasRole('super_admin') ?? false
                        )
                        ->helperText(
                            'Only Super Admin can assign roles.'
                        ),

                    Select::make('college_id')
                        ->label('Assigned College')
                        ->relationship('college', 'name')
                        ->preload()
                        ->placeholder('None (university-wide)')
                        ->visible(fn (): bool =>
                            auth()->user()?->hasRole('super_admin') ?? false
                        )
                        ->helperText(
                            'For the college_admin role: the only college this user can edit.'
                        ),
                ])
                ->columns(2),
        ]);
    }
}
