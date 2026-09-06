<?php

namespace App\Services\Voice\Support;

/**
 * Helpers for rendering page context (sent by the browser) as compact text for the model.
 */
final class Screen
{
    public static function money(mixed $value): string
    {
        return '$' . number_format((float) $value, 2);
    }

    public static function text(mixed $value, int $max = 200): string
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        $text = trim((string) ($value ?? ''));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1) . '…' : $text;
    }

    public static function quoted(mixed $value, int $max = 200): string
    {
        $text = self::text($value, $max);

        return $text === '' ? '(empty)' : '"' . $text . '"';
    }

    /**
     * Normalise a list coming from the browser: numeric keys, capped length, arrays only.
     */
    public static function list(mixed $items, int $max = 60): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_slice(array_values(array_filter($items, 'is_array')), 0, $max);
    }

    /**
     * Today's date for the model. Prefers the browser's local date (the server runs in UTC).
     */
    public static function today(?string $clientDate = null): string
    {
        $date = now();
        if ($clientDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $clientDate)) {
            try {
                $date = \Carbon\Carbon::createFromFormat('Y-m-d', $clientDate)->startOfDay();
            } catch (\Throwable $e) {
                $date = now();
            }
        }

        return $date->format('l, j F Y') . ' (' . $date->toDateString() . ')';
    }
}
