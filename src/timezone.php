<?php
declare(strict_types=1);

/** Configured administrator timezone; all persistent timestamps remain UTC. */
function admin_timezone(array $config): DateTimeZone {
    $name = $config['admin_timezone'] ?? 'America/New_York';
    if (!is_string($name) || !in_array($name, DateTimeZone::listIdentifiers(), true)) {
        throw new InvalidArgumentException('Invalid admin_timezone configuration');
    }
    return new DateTimeZone($name);
}

/** Interpret an HTML datetime-local value in the administrator timezone. */
function scheduled_local_to_utc(string $raw, DateTimeZone $zone): DateTimeImmutable {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $raw)) {
        throw new InvalidArgumentException('Invalid publication date');
    }
    $local = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $raw, $zone);
    if (!$local || $local->format('Y-m-d\TH:i') !== $raw) {
        throw new InvalidArgumentException('Invalid or nonexistent local publication time');
    }
    // During fall DST transitions, one wall-clock time can represent two instants.
    // Reject such times rather than silently choosing one.
    $offsets = [];
    $transitions = $zone->getTransitions($local->getTimestamp() - 172800, $local->getTimestamp() + 172800);
    if ($transitions !== false) {
        foreach ($transitions as $transition) $offsets[$transition['offset']] = true;
    } else {
        $offsets[$local->getOffset()] = true; // Fixed-offset zones.
    }
    $matches = 0;
    $wallAsUtc = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $raw, new DateTimeZone('UTC'));
    foreach (array_keys($offsets) as $offset) {
        $candidate = $wallAsUtc->modify(sprintf('%+d seconds', -$offset))->setTimezone($zone);
        if ($candidate->format('Y-m-d\TH:i') === $raw) $matches++;
    }
    if ($matches !== 1) {
        throw new InvalidArgumentException('Ambiguous or nonexistent local publication time; choose another time');
    }
    return $local->setTimezone(new DateTimeZone('UTC'));
}

/** Convert an existing UTC MySQL datetime to the editor's local datetime input. */
function scheduled_utc_to_local(?string $stored, DateTimeZone $zone): string {
    if (!$stored) return '';
    $utc = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $stored, new DateTimeZone('UTC'));
    if (!$utc || $utc->format('Y-m-d H:i:s') !== $stored) {
        throw new InvalidArgumentException('Invalid stored publication date');
    }
    return $utc->setTimezone($zone)->format('Y-m-d\TH:i');
}

/** Format a stored UTC timestamp for the administrator, including the timezone abbreviation. */
function admin_datetime_label(string $stored, DateTimeZone $zone): string {
    $utc = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $stored, new DateTimeZone('UTC'));
    if (!$utc || $utc->format('Y-m-d H:i:s') !== $stored) {
        throw new InvalidArgumentException('Invalid stored timestamp');
    }
    return $utc->setTimezone($zone)->format('M j, Y g:i A T');
}
