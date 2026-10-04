<?php
declare(strict_types=1);
namespace Nexa\Core;

final class Validation
{
    public static function requiredString(mixed $value, string $field, int $min = 1, int $max = 255): string
    {
        $value = trim((string)$value);
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if ($length < $min || $length > $max) {
            throw new DomainException("{$field} wajib {$min}-{$max} karakter.");
        }
        return $value;
    }

    public static function positiveInt(mixed $value, string $field): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false || $value <= 0) {
            throw new DomainException("{$field} harus bilangan bulat positif.");
        }
        return $value;
    }

    public static function nonNegativeFloat(mixed $value, string $field): float
    {
        if (!is_numeric($value) || (float)$value < 0) {
            throw new DomainException("{$field} tidak boleh negatif.");
        }
        return (float)$value;
    }

    public static function date(mixed $value, string $field = 'Tanggal'): string
    {
        $value = (string)$value;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) || $date->format('Y-m-d') !== $value) {
            throw new DomainException("{$field} harus berformat YYYY-MM-DD.");
        }
        return $value;
    }

    public static function period(mixed $value): string
    {
        $value = (string)$value;
        if (!preg_match('/^\\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            throw new DomainException('Periode harus berformat YYYY-MM.');
        }
        return $value;
    }

    public static function currency(mixed $value): string
    {
        $code = strtoupper(trim((string)$value));
        if (!preg_match('/^[A-Z]{3}$/', $code)) {
            throw new DomainException('Kode mata uang harus ISO 4217 tiga huruf.');
        }
        return $code;
    }
}
