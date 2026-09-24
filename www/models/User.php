<?php
declare(strict_types=1);

class User
{
    public function __construct(
        protected string $id,
        protected string $name,
        protected string $lastName,
        protected string $email,
        protected string $passwordHash,
        protected string $role,
        protected DateTimeImmutable $created,
    ) {}

    public function setId(string $value): void
    {
        $this->id = $value;
    }
    public function getId(): string
    {
        return $this->id;
    }

    public function setName(string $value): void
    {
        $this->name = $value;
    }
    public function getName(): string
    {
        return $this->name;
    }

    public function setLastName(string $value): void
    {
        $this->lastName = $value;
    }
    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setEmail(string $value): void
    {
        $this->email = $value;
    }
    public function getEmail(): string
    {
        return $this->email;
    }

    public function setPasswordHash(string $value): void
    {
        $this->passwordHash = $value;
    }
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setRole(string $value): void
    {
        $this->role = $value;
    }
    public function getRole(): string
    {
        return $this->role;
    }

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

    /** @return array<string, mixed> */
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
}
?>
