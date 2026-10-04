<?php
declare(strict_types=1);
namespace Nexa\Shared;

use Nexa\Core\DomainException;
use Nexa\Core\Validation;

final readonly class Money
{
    public function __construct(public int $minor, public string $currency = 'IDR', public int $scale = 0)
    {
        Validation::currency($currency);
        if ($scale < 0 || $scale > 6) {
            throw new DomainException('Skala mata uang tidak valid.');
        }
    }

    public static function fromFloat(float $amount, string $currency = 'IDR', int $scale = 0): self
    {
        return new self((int)round($amount * (10 ** $scale)), $currency, $scale);
    }

    public static function fromString(string $amount, string $currency = 'IDR', int $scale = 0): self
    {
        $normalized = str_replace([' ', ','], ['', '.'], trim($amount));
        if (!is_numeric($normalized)) {
            throw new DomainException('Nominal uang tidak valid.');
        }
        return self::fromFloat((float)$normalized, $currency, $scale);
    }

    public function toFloat(): float { return $this->minor / (10 ** $this->scale); }

    public function add(self $other): self
    {
        $this->assertCompatible($other);
        return new self($this->minor + $other->minor, $this->currency, $this->scale);
    }

    public function subtract(self $other): self
    {
        $this->assertCompatible($other);
        return new self($this->minor - $other->minor, $this->currency, $this->scale);
    }

    public function negate(): self { return new self(-$this->minor, $this->currency, $this->scale); }
    public function isZero(): bool { return $this->minor === 0; }
    public function isPositive(): bool { return $this->minor > 0; }

    private function assertCompatible(self $other): void
    {
        if ($other->currency !== $this->currency || $other->scale !== $this->scale) {
            throw new DomainException('Mata uang atau skala nominal berbeda.');
        }
    }
}
