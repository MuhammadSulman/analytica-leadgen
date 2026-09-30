<?php

namespace App\Models;

use App\Enums\LeadPlatform;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'company',
        'phone',
        'message',
        'status',
        'source',
        'platform',
    ];

    /**
     * The admins' notes on this lead, newest first.
     *
     * @return HasMany<LeadNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest()->latest('id');
    }

    /**
     * Apply the admin leads list search and platform/status filters.
     * Shared by the Leads page and its CSV export so both always match.
     *
     * @param  Builder<Lead>  $query
     * @param  array{search?: string, platforms?: array<int, string>, statuses?: array<int, string>, from?: ?string, to?: ?string, timezone?: string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $search = trim($filters['search'] ?? '');
        $zone = $filters['timezone'] ?? config('app.timezone');

        $query
            ->when($search !== '', function (Builder $query) use ($search) {
                // A substring of name, email, company or phone; % and _ are matched literally.
                $term = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->whereLike('name', $term)
                    ->orWhereLike('email', $term)
                    ->orWhereLike('company', $term)
                    ->orWhereLike('phone', $term));
            })
            ->when($filters['platforms'] ?? [], fn (Builder $query, array $platforms) => $query->whereIn('platform', $platforms))
            ->when($filters['statuses'] ?? [], fn (Builder $query, array $statuses) => $query->whereIn('status', $statuses))
            // Whole days in the admin's time zone, inclusive of both ends, converted to the
            // app time zone that created_at is stored in.
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query
                ->where('created_at', '>=', Carbon::parse($from, $zone)->startOfDay()->setTimezone(config('app.timezone'))))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query
                ->where('created_at', '<', Carbon::parse($to, $zone)->addDay()->startOfDay()->setTimezone(config('app.timezone'))));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => LeadPlatform::class,
        ];
    }
}
