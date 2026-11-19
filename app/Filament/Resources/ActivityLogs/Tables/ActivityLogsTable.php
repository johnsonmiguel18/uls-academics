<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Models\ActivityLog;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date and Time')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('md'),

                TextColumn::make('actor_name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): ?string => $record->actor_email)
                    ->placeholder('System / Guest'),

                TextColumn::make('actor_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('actor_roles')
                    ->label('Role')
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->placeholder('—')
                    ->badge()
                    ->toggleable()
                    ->visibleFrom('lg'),

                TextColumn::make('event')
                    ->label('Action')
                    ->formatStateUsing(fn (string $state): string => ActivityLog::EVENTS[$state] ?? Str::headline($state))
                    ->color(fn (string $state): string => in_array($state, ['auth.failed', 'auth.denied'], true) || str_ends_with($state, '.deleted') ? 'danger' : 'primary')
                    // On phones the date column is hidden, so show the time here.
                    ->description(fn ($record): string => $record->created_at->diffForHumans())
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('description')
                    ->label('Activity')
                    ->searchable()
                    ->wrap()
                    ->limit(100)
                    ->visibleFrom('md'),

                TextColumn::make('subject_type')
                    ->label('Record Type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subject_id')
                    ->label('Record ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Action Type')
                    ->options(ActivityLog::EVENTS),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
