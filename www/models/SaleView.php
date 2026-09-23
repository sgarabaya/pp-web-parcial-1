<?php

class SaleView
{
    public function __construct(
        public string $id,
        public string $user,
        public string $vehicle,
        public float $paidAmount,
        public float $suggestedPrice,
        public string $clientName,
        public string $clientContact,
        public string $paymentMethod,
        public DateTimeImmutable $created,
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function mapFrom(array $data): self
    {
        return new self(
            id: $data["id"],
            user: $data["user"],
            vehicle: $data["vehicle"],
            paidAmount: (float) $data["paid_amount"],
            suggestedPrice: (float) $data["suggested_price"],
            clientName: $data["client_name"],
            clientContact: $data["client_contact"],
            paymentMethod: $data["payment_method"],
            created: new DateTimeImmutable($data["created"]),
        );
    }
}

?>
