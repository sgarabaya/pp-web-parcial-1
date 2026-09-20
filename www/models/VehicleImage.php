<?php
declare(strict_types=1);

class VehicleImage
{
    public function __construct(
        public string $id,
        public string $vehicleId,
        public string $url,
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
            vehicleId: $data["vehicle_id"],
            url: $data["url"],
        );
    }
}

?>
