<?php

/** @extends Repository<User> */
class UserRepository extends Repository
{
    #[\Override]
    protected function getTableName(): string
    {
        return "Users";
    }

    #[\Override]
    protected function getColumns(): array
    {
        return ["name", "last_name", "email", "password_hash", "role"];
    }

    #[\Override]
    protected function mapFrom(array $row): ?User
    {
        return User::mapFrom($row);
    }

    #[\Override]
    protected function mapTo(object $obj): array
    {
        /** @var User $obj */
        return $obj->mapTo();
    }

    public function findByEmail(string $email): ?User
    {
        $r = $this->findBy("email", $email);
        return count($r) > 0 ? $r[0] : null;
    }

    #[\Override]
    public function update(string $id, array $partialData): bool
    {
        if (
            isset($partialData["password"]) &&
            !empty($partialData["password"])
        ) {
            $partialData["password_hash"] = Crypto::passwordHash(
                $partialData["password"],
            );
            unset($partialData["password"]);
        }

        return parent::update($id, $partialData);
    }
}
