<?php
declare(strict_types=1);
namespace Nexa\Core;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
