<?php

/** @template T */
abstract class Repository
{
    abstract protected function getTableName(): string;

    /**
     * @param array<mixed> $row
     * @return T|null
     */
    abstract protected function mapFrom(array $row): ?object;

    /** @return array<string,mixed> */
    abstract protected function mapTo(object $obj): array;

    /** @return T|null */
    public function findById(string $id): ?object
    {
        $query = sprintf(
            "SELECT * FROM %s WHERE id = ? LIMIT 1",
            $this->getTableName(),
        );

        $stmt = Database::connect()->prepare($query);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapFrom($row) : null;
    }

    /** @return array<T> */
    public function findAll(): array
    {
        $query = sprintf("SELECT * FROM %s", $this->getTableName());

        $stmt = Database::connect()->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows ? array_map(fn($row) => $this->mapFrom($row), $rows) : [];
    }

    /** @return array<T> */
    public function findBy(string $column, mixed $value): array
    {
        $query = sprintf(
            "SELECT * FROM %s WHERE %s = ?",
            $this->getTableName(),
            $column,
        );

        $stmt = Database::connect()->prepare($query);
        $stmt->execute([$value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows ? array_map(fn($row) => $this->mapFrom($row), $rows) : [];
    }

    public function delete(string $id): bool
    {
        $query = sprintf("DELETE FROM %s WHERE id = ?", $this->getTableName());
        $stmt = Database::connect()->prepare($query);
        return $stmt->execute([$id]);
    }

    public function create(object $obj): bool
    {
        $row = $this->mapTo($obj);
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES(%s)",
            $this->getTableName(),
            implode(", ", array_keys($row)),
            implode(", ", array_map(fn($k) => "?", array_keys($row))),
        );

        $stmt = Database::connect()->prepare($query);
        return $stmt->execute(array_values($row));
    }

    public function update(object $obj): bool
    {
        $row = $this->mapTo($obj);
        $id = $row["id"];
        unset($row["id"]);

        $fields = array_map(fn($k) => "$k = ?", array_keys($row));
        $query = sprintf(
            "UPDATE %s SET %s WHERE id = ?",
            $this->getTableName(),
            implode(", ", $fields),
        );

        $params = array_values($row);
        $params[] = $id;

        $stmt = Database::connect()->prepare($query);
        return $stmt->execute($params);
    }
}

?>
