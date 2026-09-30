<?php

namespace App\Http\Requests;

use App\Enums\LeadPlatform;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The search, filter, sort and paging options for the admin leads list and its CSV export.
 */
class LeadFilterRequest extends FormRequest
{
    public const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Drop anything invalid so a mistyped or outdated URL falls back to the defaults
     * instead of failing. The rules below then only ever see clean values.
     */
    protected function prepareForValidation(): void
    {
        $choices = [
            'view' => ['active', 'deleted'],
            'sort' => ['name', 'created_at', 'deleted_at'],
            'direction' => ['asc', 'desc'],
            'per_page' => array_map('strval', self::PER_PAGE_OPTIONS),
        ];

        foreach ($choices as $key => $allowed) {
            if ($this->has($key) && ! in_array($this->input($key), $allowed, true)) {
                $this->offsetUnset($key);
            }
        }

        $search = $this->input('search');

        if ($this->has('search') && ! is_string($search)) {
            $this->offsetUnset('search');
        } elseif (is_string($search)) {
            $this->merge(['search' => mb_substr($search, 0, 255)]);
        }

        $this->keepOnlyValid('platforms', fn ($value) => LeadPlatform::tryFrom($value) !== null);
        $this->keepOnlyValid('statuses', fn ($value) => in_array($value, ['new', 'contacted', 'follow_up', 'won', 'lost'], true));
        $this->keepOnlyValid('ids', fn ($value) => ctype_digit($value));

        foreach (['from', 'to'] as $key) {
            if ($this->has($key) && ! $this->isDate($this->input($key))) {
                $this->offsetUnset($key);
            }
        }

        // A backwards range (from after to) is almost certainly the two dates swapped.
        if ($this->filled('from') && $this->filled('to') && $this->input('from') > $this->input('to')) {
            $this->merge(['from' => $this->input('to'), 'to' => $this->input('from')]);
        }

        if ($this->has('page') && ! (is_string($this->input('page')) && ctype_digit($this->input('page')) && $this->input('page') > 0)) {
            $this->offsetUnset('page');
        }
    }

    /**
     * The admin's time zone, reported by their browser in the "tz" cookie, so a date
     * filter covers that whole day locally. Falls back to the app time zone (UTC).
     */
    public function timezone(): string
    {
        $zone = $this->cookie('tz');

        if (! is_string($zone) || $zone === '') {
            return config('app.timezone');
        }

        try {
            return (new DateTimeZone($zone))->getName();
        } catch (Exception) {
            return config('app.timezone');
        }
    }

    /**
     * Whether the value is a real calendar date written as YYYY-MM-DD.
     */
    private function isDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    /**
     * Keep only the valid entries of a list parameter, dropping it entirely if it isn't a list.
     */
    private function keepOnlyValid(string $key, callable $isValid): void
    {
        if (! $this->has($key)) {
            return;
        }

        $values = $this->input($key);

        if (! is_array($values)) {
            $this->offsetUnset($key);

            return;
        }

        $this->merge([$key => array_values(array_unique(array_filter(
            $values,
            fn ($value) => is_string($value) && $isValid($value),
        )))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'view' => ['sometimes', 'in:active,deleted'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'platforms' => ['sometimes', 'array'],
            'platforms.*' => [Rule::enum(LeadPlatform::class)],
            'statuses' => ['sometimes', 'array'],
            'statuses.*' => ['in:new,contacted,follow_up,won,lost'],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d'],
            'sort' => ['sometimes', 'in:name,created_at,deleted_at'],
            'direction' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'page' => ['sometimes', 'integer', 'min:1'],
            'ids' => ['sometimes', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ];
    }

    /**
     * The applied options, with defaults filled in.
     *
     * @return array{view: string, search: string, platforms: array<int, string>, statuses: array<int, string>, from: ?string, to: ?string, sort: string, direction: string}
     */
    public function filters(): array
    {
        $deleted = $this->validated('view') === 'deleted';

        return [
            'view' => $deleted ? 'deleted' : 'active',
            'search' => trim((string) $this->validated('search')),
            'platforms' => $this->validated('platforms', []),
            'statuses' => $this->validated('statuses', []),
            // Date received, inclusive on both ends.
            'from' => $this->validated('from'),
            'to' => $this->validated('to'),
            // Newest first by default: by deletion date in the Deleted tab, otherwise by date received.
            'sort' => $this->validated('sort', $deleted ? 'deleted_at' : 'created_at'),
            'direction' => $this->validated('direction', 'desc'),
        ];
    }
}
