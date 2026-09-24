<?php
declare(strict_types=1);

class Employee extends User
{
    protected function assertValidRole(string $role): void
    {
        if ($role === "ADMIN") {
            throw new InvalidArgumentException(Messages::wrongRole());
        }
    }

    public function canSee(string $page): bool
    {
        switch ($page) {
            case "OVERVIEW":
            case "STOCK":
                return true;
            case "SALES":
                return $this->getRole() === "SALES";
            default:
                return false;
        }
    }

    public function canEdit(string $entity): bool
    {
        return $this->getRole() === $entity;
    }
}
