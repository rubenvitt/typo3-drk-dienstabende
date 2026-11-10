<?php

declare(strict_types=1);

namespace Rubeen\DrkDienstabende\Domain;

/**
 * Turns the Dienstabende JSON into view data for the Fluid template.
 *
 * Framework-free on purpose: if the real source ships a different schema,
 * only this class changes. Expected input:
 *
 *   {"events": [{"start": "2026-11-11T19:00:00+01:00" | "2026-11-11",
 *                "title": "…", "description": "…", "location": "…"}]}
 */
final class EventMapper
{
    private const TIMEZONE = 'Europe/Berlin';
    private const ISO_DATE = '/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}(:\d{2})?(Z|[+-]\d{2}:?\d{2})?)?$/';

    /**
     * Upcoming events (today included), sorted by start, at most $limit (0 = all).
     * Invalid entries are skipped, never fatal: the page must render anyway.
     *
     * @return list<array{start: \DateTimeImmutable, time: ?string, dayShort: string, dayLong: string, month: string, title: string, text: string, location: string}>
     */
    public static function map(mixed $json, \DateTimeImmutable $now, int $limit = 0): array
    {
        $timezone = new \DateTimeZone(self::TIMEZONE);
        $today = $now->setTimezone($timezone)->setTime(0, 0);
        $events = [];
        foreach (is_array($json) && is_array($json['events'] ?? null) ? $json['events'] : [] as $raw) {
            $start = is_array($raw) ? ($raw['start'] ?? null) : null;
            $title = is_array($raw) && is_string($raw['title'] ?? null) ? trim($raw['title']) : '';
            if (!is_string($start) || !preg_match(self::ISO_DATE, $start) || $title === '') {
                continue;
            }
            try {
                // A given offset wins over $timezone; date-only and offset-less values are Berlin time.
                $date = (new \DateTimeImmutable($start, $timezone))->setTimezone($timezone);
            } catch (\Throwable) { // Throwable: with Xdebug a malformed date surfaces as Error, not Exception
                continue;
            }
            if ($date < $today) {
                continue;
            }
            $events[] = [
                'start' => $date,
                // Date-only entries are all-day: no "00:00 Uhr" (or "01:00 Uhr" from midnight UTC).
                'time' => strlen($start) === 10 ? null : $date->format('H:i'),
                'dayShort' => self::format($date, 'EEE, dd.MM.'),
                'dayLong' => self::format($date, 'EEEE, d. MMMM'),
                'month' => self::format($date, 'MMMM yyyy'),
                'title' => $title,
                'text' => is_string($raw['description'] ?? null) ? trim($raw['description']) : '',
                'location' => is_string($raw['location'] ?? null) ? trim($raw['location']) : '',
            ];
        }
        usort($events, static fn(array $a, array $b): int => $a['start'] <=> $b['start']);
        return $limit > 0 ? array_slice($events, 0, $limit) : $events;
    }

    private static function format(\DateTimeImmutable $date, string $pattern): string
    {
        return (string)(new \IntlDateFormatter('de_DE', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, self::TIMEZONE, null, $pattern))->format($date);
    }
}
