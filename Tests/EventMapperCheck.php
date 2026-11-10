<?php

// Self-check without PHPUnit: php Tests/EventMapperCheck.php (needs ext-intl)
declare(strict_types=1);

require __DIR__ . '/../Classes/Domain/EventMapper.php';

use Rubeen\DrkDienstabende\Domain\EventMapper;

ini_set('zend.assertions', '1');
ini_set('assert.exception', '1');

$now = new DateTimeImmutable('2026-11-11T20:30:00+01:00');
$json = ['events' => [
    ['start' => '2026-11-25T19:00:00+01:00', 'title' => 'Später', 'description' => 'B', 'location' => 'Uelzen'],
    ['start' => '2026-11-04T19:00:00+01:00', 'title' => 'Vergangen'],
    ['start' => '2026-11-11T18:00:00Z', 'title' => 'Heute, UTC'],
    ['start' => '2026-12-02', 'title' => 'Ganztägig'],
    ['start' => 'next monday', 'title' => 'Freitext-Datum'],
    ['start' => '2026-11-18T19:00:00+01:00', 'title' => '  '],
    ['start' => '2026-13-45T19:00', 'title' => 'Kaputtes Datum'],
    'kein Objekt',
]];

$events = EventMapper::map($json, $now);
assert(array_column($events, 'title') === ['Heute, UTC', 'Später', 'Ganztägig'], 'filters past/invalid, sorts');
assert($events[0]['time'] === '19:00', 'UTC is shown in Berlin time');
assert($events[0]['dayShort'] === 'Mi., 11.11.');
assert($events[1]['dayLong'] === 'Mittwoch, 25. November');
assert($events[1]['month'] === 'November 2026');
assert($events[2]['time'] === null, 'date-only has no time');
assert(count(EventMapper::map($json, $now, 2)) === 2, 'limit');
assert(EventMapper::map(null, $now) === [] && EventMapper::map(['events' => 'x'], $now) === [] && EventMapper::map([1, 2], $now) === []);

echo "ok\n";
