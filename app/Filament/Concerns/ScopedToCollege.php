<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Academics resources: users with "manage_academics" see every college;
 * a college_admin sees and edits only records of their own college (users.college_id).
 */
trait ScopedToCollege
{
    /** Column tying a record to its college. CollegeResource overrides this with "id". */
    protected static function collegeKey(): string
    {
        return 'college_id';
    }

    public static function managesAllColleges(): bool
    {
        return auth()->user()?->can('manage_academics') ?? false;
    }

    public static function ownCollegeId(): ?int
    {
        $user = auth()->user();

        return $user?->hasRole('college_admin') ? $user->college_id : null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return static::managesAllColleges()
            ? $query
            : $query->where(static::collegeKey(), static::ownCollegeId() ?? 0);
    }

    public static function canViewAny(): bool
    {
        return static::managesAllColleges() || static::ownCollegeId() !== null;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::managesAllColleges()
            || (static::ownCollegeId() !== null && (int) $record->getAttribute(static::collegeKey()) === static::ownCollegeId());
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function canDeleteAny(): bool
    {
        return static::managesAllColleges();
    }

    /** A college admin without "manage_academics": works on their own college only. */
    public static function limitedToOwnCollege(): bool
    {
        return ! static::managesAllColleges() && (auth()->user()?->hasRole('college_admin') ?? false);
    }

    /**
     * College picker: a college admin only gets their own college, preselected and required.
     * Everyone else who can open the form (managers, university-wide news editors) gets every college.
     */
    public static function collegeSelect(bool $required = true): Select
    {
        return Select::make('college_id')
            ->label('College / Academic Unit')
            ->relationship('college', 'name', fn (Builder $query) => static::limitedToOwnCollege()
                ? $query->whereKey(static::ownCollegeId() ?? 0)
                : $query->orderBy('sort_order'))
            ->default(fn () => static::ownCollegeId())
            ->required($required || static::limitedToOwnCollege())
            ->live()
            ->preload()
            ->searchable();
    }
}
