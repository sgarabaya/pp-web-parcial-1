<?php
declare(strict_types=1);

class Sale
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public string $vehicleId,
        public float $paidAmount,
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
            employeeId: $data["employee_id"],
            vehicleId: $data["vehicle_id"],
            paidAmount: (float) $data["paid_amount"],
            created: new DateTimeImmutable($data["created"]),
        );
    }
}

?>
