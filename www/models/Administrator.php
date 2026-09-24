<?php
declare(strict_types=1);

class Administrator extends User
{
    protected function assertValidRole(string $role): void
    {
        if ($role !== "ADMIN") {
            throw new InvalidArgumentException(Messages::wrongRole());
        }
    }

    //Admin can see everything
    public function canSee(string $page): bool
    {
        return true;
    }

    //Admin can do everything
    public function canEdit(string $entity): bool
    {
        return true;
    }

    //Always yes
    public function satisfies(string $requiredRole): bool
    {
        return true;
    }
}
