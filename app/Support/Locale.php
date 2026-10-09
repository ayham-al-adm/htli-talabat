<?php

namespace App\Support;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Throwable;

/**
 * Single source of truth for the locale used by a request, shared by the
 * server (translations, landing CMS content) and the Vue app (vue-i18n).
 */
class Locale
{
    public const COOKIE = 'locale';

    /** @var array<string, string>|null code => direction, memoized per request */
    protected static ?array $languages = null;

    protected static ?string $default = null;

    /**
     * Active languages as [code => direction], codes lowercased.
     */
    public static function languages(): array
    {
        if (static::$languages === null) {
            try {
                $rows = Languages::where('active', true)->get(['code', 'direction', 'default_status']);

                static::$languages = $rows->mapWithKeys(fn ($l) => [strtolower($l->code) => $l->direction ?: 'ltr'])->all();

                $default = $rows->firstWhere('default_status', true);
                static::$default = $default ? strtolower($default->code) : null;
            } catch (Throwable $e) {
                static::$languages = [];
            }

            if (empty(static::$languages)) {
                static::$languages = ['en' => 'ltr'];
            }
        }

        return static::$languages;
    }

    public static function default(): string
    {
        $languages = static::languages();

        if (static::$default && isset($languages[static::$default])) {
            return static::$default;
        }

        return isset($languages['en']) ? 'en' : array_key_first($languages);
    }

    /**
     * Return the lowercase code if it is an active language, otherwise null.
     * Accepts values such as "AR", "ar-SA" or "ar_SA".
     */
    public static function normalize(?string $locale): ?string
    {
        if (!is_string($locale) || $locale === '') {
            return null;
        }

        $locale = strtolower(trim($locale));
        $languages = static::languages();

        if (isset($languages[$locale])) {
            return $locale;
        }

        $base = preg_split('/[-_]/', $locale)[0];

        return isset($languages[$base]) ? $base : null;
    }

    /**
     * Resolve the request locale: ?locale → X-Locale header → locale cookie (web)
     * → default language (web) / configured app locale (API).
     */
    public static function resolve(Request $request, bool $isApi = false): string
    {
        // API clients opt in explicitly (?locale or X-Locale); browser headers such as
        // Accept-Language are ignored so mobile apps keep receiving stable messages.
        $candidates = [
            $request->query('locale'),
            $request->header('X-Locale'),
        ];

        if (!$isApi) {
            $candidates[] = $request->cookie(static::COOKIE);
        }

        foreach ($candidates as $candidate) {
            if ($locale = static::normalize($candidate)) {
                return $locale;
            }
        }

        // Mobile clients that send no language keep the app's configured locale.
        if ($isApi) {
            return static::normalize(config('app.locale')) ?? static::default();
        }

        return static::default();
    }

    public static function direction(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return static::languages()[strtolower($locale)] ?? 'ltr';
    }

    /**
     * Ordered fallback list used for CMS content: current, default, English.
     */
    public static function candidates(?string $preferred = null): array
    {
        return array_values(array_unique(array_filter([
            strtolower($preferred ?? app()->getLocale()),
            static::default(),
            'en',
        ])));
    }

    /**
     * First row of a locale-keyed table (landing CMS) in fallback order.
     */
    public static function pick(Builder $query, ?string $preferred = null): ?Model
    {
        $candidates = static::candidates($preferred);
        $placeholders = implode(',', array_fill(0, count($candidates), '?'));

        return $query->whereIn('locale', $candidates)
            ->orderByRaw("FIELD(LOWER(locale), {$placeholders})", $candidates)
            ->first();
    }

    /**
     * Switch the request locale to the language the page content was actually
     * found in, so static UI strings and CMS content never mix languages.
     */
    public static function alignTo(?Model $content): string
    {
        $locale = static::normalize($content?->locale) ?? app()->getLocale();

        app()->setLocale($locale);

        return $locale;
    }
}
