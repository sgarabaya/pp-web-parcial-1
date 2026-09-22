<?php
declare(strict_types=1);

class User
{
    public function __construct(
        public string $id,
        public string $name,
        public string $lastName,
        public string $email,
        public string $passwordHash,
        public string $role,
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
            name: $data["name"],
            lastName: $data["last_name"],
            email: $data["email"],
            passwordHash: $data["password_hash"],
            role: $data["role"],
            created: new DateTimeImmutable($data["created"]),
        );
    }
    /**
     * @return array<string>
     */
    public function mapTo(): array
    {
        return [
            "id" => $this->id,
            "email" => $this->email,
            "name" => $this->name,
            "last_name" => $this->lastName,
            "password_hash" => $this->passwordHash,
            "role" => $this->role,
            "created" => $this->created->format(DateTimeInterface::ATOM),
        ];
    }

    /** @@return array<mixed> */
    public function serialize(): array
    {
        $obj = $this->mapTo();
        unset($obj["password_hash"]);
        return $obj;
    }
}
?>
