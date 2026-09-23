<?php
declare(strict_types=1);

class Vehicle
{
    public function __construct(
        public string $id,
        public string $brand,
        public string $model,
        public int $year,
        public float $price,
        public int $stock,
        public DateTimeImmutable $created,
    ) {}

    /**
     * Creates an instance from an array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function mapFrom(array $data): self
    {
        return new self(
            id: $data["id"],
            brand: $data["brand"],
            model: $data["model"],
            year: (int) $data["year"],
            price: (float) $data["price"],
            stock: (int) $data["stock"],
            created: new DateTimeImmutable($data["created"]),
        );
    }

    /** @return array<string, mixed> */
    public function mapTo(): array
    {
        return [
            "id" => $this->id,
            "brand" => $this->brand,
            "model" => $this->model,
            "year" => $this->year,
            "price" => $this->price,
            "stock" => $this->stock,
            "created" => $this->created->format(DateTimeInterface::ATOM),
        ];
    }
}

?>
