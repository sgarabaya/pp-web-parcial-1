<?php

/** @extends Repository<Vehicle> */
class VehicleRepository extends Repository
{
    #[\Override]
    protected function getTableName(): string
    {
        return "Vehicles";
    }

    #[\Override]
    protected function getColumns(): array
    {
        return ["brand", "model", "year", "price", "stock"];
    }

    #[\Override]
    protected function mapFrom(array $row): ?object
    {
        return Vehicle::mapFrom($row);
    }

    #[\Override]
    protected function mapTo(object $obj): array
    {
        /** @var Vehicle $obj */
        return $obj->mapTo();
    }
}
