<?php
declare(strict_types=1);
namespace Nexa\Core;

final class FrozenClock implements Clock
{
    public function __construct(private \DateTimeImmutable $value) {}
    public function now(): \DateTimeImmutable { return $this->value; }
    public function travelTo(\DateTimeImmutable $value): void { $this->value = $value; }
}
