<?php
declare(strict_types=1);
namespace Nexa\Core;

final class Ids
{
    public static function token(string $prefix, int $bytes = 6): string
    {
        return strtoupper($prefix . '-' . bin2hex(random_bytes($bytes)));
    }

    public static function journalNo(string $date, string $prefix = 'JRN'): string
    {
        Validation::date($date);
        return strtoupper($prefix) . '-' . str_replace('-', '', substr($date, 0, 7)) . '-' . strtoupper(bin2hex(random_bytes(4)));
    }
}
