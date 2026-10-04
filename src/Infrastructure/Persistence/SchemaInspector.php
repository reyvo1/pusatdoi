<?php
declare(strict_types=1);
namespace Nexa\Infrastructure\Persistence;

final class SchemaInspector
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<string> */
    public function tables(): array
    {
        $stmt = $this->pdo->query(
            "SELECT TABLE_NAME AS table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY TABLE_NAME"
        );
        $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        return array_values(array_map(static fn($v): string => (string)$v, $rows));
    }

    /** @param list<string> $required */
    public function assertRequired(array $required): array
    {
        $existing = array_flip($this->tables());
        $missing = array_values(array_filter($required, static fn($t) => !isset($existing[$t])));
        return ['ok' => !$missing, 'missing' => $missing, 'table_count' => count($existing)];
    }
}
