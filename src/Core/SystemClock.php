<?php
declare(strict_types=1);
namespace Nexa\Core;

final class SystemClock implements Clock
{
    public function __construct(private readonly \DateTimeZone $timezone) {}
    public function now(): \DateTimeImmutable { return new \DateTimeImmutable('now', $this->timezone); }
}
