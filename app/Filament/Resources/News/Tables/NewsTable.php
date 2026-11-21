<?php

namespace App\Filament\Resources\News\Tables;

use App\Models\News;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')->disabledClick()
                    ->label('')
                    ->disk('public')
                    ->imageWidth(72)
                    ->imageHeight(48),

                TextColumn::make('title')
                    ->label('Article Title')
                    ->description(fn (News $record): ?string => $record->excerpt ? \Illuminate\Support\Str::limit($record->excerpt, 70) : null)
                    ->searchable(['title', 'excerpt'])
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => \App\Models\Term::label('news_category', $state))
                    ->color(fn (string $state): string => $state === 'announcement' ? 'gray' : 'primary')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (News $record): string => News::STATES[$record->state()][0])
                    ->color(fn (News $record): string => match ($record->state()) {
                        'live' => 'success',
                        'scheduled' => 'info',
                        'review' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(['status'])
                    ->visibleFrom('sm'),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon(Heroicon::Star)
                    ->falseIcon(Heroicon::OutlinedStar)
                    ->trueColor('primary')
                    ->falseColor('gray')
                    ->visibleFrom('lg'),

                TextColumn::make('published_at')
                    ->label('Publish date')
                    ->dateTime('M d, Y g:i A')
                    ->description(fn (News $record): ?string => $record->unpublish_at ? 'Until ' . $record->unpublish_at->format('M d, Y') : null)
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn () => \App\Models\Term::options('news_category')),

                SelectFilter::make('status')
                    ->options(News::STATUSES),
            ])
            ->defaultSort('published_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (News $record): string => route('news.show', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (News $record): bool => $record->state() === 'live'),

                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn (): bool =>
                        auth()->user()?->can('delete_news') ?? false
                    )
                    ->authorize(fn (): bool =>
                        auth()->user()?->can('delete_news') ?? false
                    )
                    ->requiresConfirmation(),
            ]);
    }
}
