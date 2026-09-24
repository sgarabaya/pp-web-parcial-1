<?php
declare(strict_types=1);

abstract class User
{
    public function __construct(
        protected string $id,
        protected string $name,
        protected string $lastName,
        protected string $email,
        protected string $passwordHash,
        protected string $role,
        protected DateTimeImmutable $created,
    ) {
        $this->assertValidRole($role);
    }

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
        $this->assertValidRole($value);
        $this->role = $value;
    }
    public function getRole(): string
    {
        return $this->role;
    }

    public function displayName(): string
    {
        return sprintf(
            "%s.%s",
            substr($this->getName(), 0, 1),
            $this->getLastName(),
        );
    }

    protected function assertValidRole(string $role): void {}

    abstract public function canSee(string $page): bool;

    abstract public function canEdit(string $entity): bool;

    public function satisfies(string $requiredRole): bool
    {
        return $this->role === $requiredRole;
    }

    public static function create(
        string $id,
        string $name,
        string $lastName,
        string $email,
        string $passwordHash,
        string $role,
        DateTimeImmutable $created,
    ): self {
        return self::fromFields(
            id: $id,
            name: $name,
            lastName: $lastName,
            email: $email,
            role: $role,
            passwordHash: $passwordHash,
            created: $created,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function mapFrom(array $data): self
    {
        return self::fromFields(
            id: $data["id"],
            name: $data["name"],
            lastName: $data["last_name"],
            email: $data["email"],
            role: $data["role"],
            passwordHash: $data["password_hash"],
            created: new DateTimeImmutable($data["created"]),
        );
    }

    // Select the proper subclass depending on role
    private static function fromFields(
        string $id,
        string $name,
        string $lastName,
        string $email,
        string $role,
        string $passwordHash,
        DateTimeImmutable $created,
    ): self {
        if ($role === "ADMIN") {
            return new Administrator(
                id: $id,
                name: $name,
                lastName: $lastName,
                email: $email,
                passwordHash: $passwordHash,
                role: $role,
                created: $created,
            );
        }
        return new Employee(
            id: $id,
            name: $name,
            lastName: $lastName,
            email: $email,
            passwordHash: $passwordHash,
            role: $role,
            created: $created,
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
