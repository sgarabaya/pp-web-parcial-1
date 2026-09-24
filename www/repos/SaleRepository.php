<?php

/** @extends Repository<Sale> */
class SaleRepository extends Repository
{
    #[\Override]
    protected function getTableName(): string
    {
        return "Sales";
    }

    #[\Override]
    protected function getColumns(): array
    {
        return [
            "user_id",
            "vehicle_id",
            "paid_amount",
            "client_name",
            "client_contact",
            "payment_method",
        ];
    }

    #[\Override]
    protected function mapFrom(array $row): ?object
    {
        return Sale::mapFrom($row);
    }

    #[\Override]
    protected function mapTo(object $obj): array
    {
        /** @var Sale $obj */
        return $obj->mapTo();
    }

    public function registerSale(Sale $sale): void
    {
        $userRepo = new UserRepository();
        $vehicleRepo = new VehicleRepository();

        $user = $userRepo->findById($sale->userId);
        $vehicle = $vehicleRepo->findById($sale->vehicleId);

        if (!$user) {
            throw new Exception(Messages::doesntExist("Empleado"));
        }
        if (!$vehicle) {
            throw new Exception(Messages::doesntExist("Vehiculo"));
        }
        if ($vehicle->stock === 0) {
            throw new Exception(Messages::operationFailed());
        }

        $conn = Database::connect();
        try {
            $conn->beginTransaction();

            $this->create($sale);
            $vehicle->stock -= 1;
            $vehicleRepo->update($vehicle->id, $vehicle->mapTo());

            $conn->commit();
        } catch (Exception $ex) {
            $conn->rollBack();
            throw $ex;
        }
    }

    //Fetch custom para poder ver todos los detalles
    /** @return SaleView[] */
    public function fetchDetails(): array
    {
        $query = "SELECT
            S.id,
            CONCAT(U.name, ' ', U.last_name) AS `user`,
            CONCAT(V.brand, ' ', V.model, ' (', V.year, ')') AS `vehicle`,
            S.paid_amount,
            V.price AS `suggested_price`,
            S.client_name,
            S.client_contact,
            S.payment_method,
            S.created
        FROM Sales S
        INNER JOIN Users U ON U.id = S.user_id
        INNER JOIN Vehicles V ON V.id = S.vehicle_id
        ORDER BY S.created DESC
        ;";

        $stmt = Database::connect()->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => SaleView::mapFrom((array) $row), $rows);
    }

    /** @return mixed[] */
    public function fetchSalesOverview(): array
    {
        $query = "
            SELECT
                U.id,
                CONCAT(U.name, ' ', U.last_name)    AS `userName`,
                COUNT(S.id)                         AS `salesCount`,
                SUM(S.paid_amount)                  AS `totalAmount`
            FROM Sales S
            INNER JOIN Users U ON U.id = S.user_id
            GROUP BY 1, 2;
        ";

        $stmt = Database::connect()->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => (array) $row, $rows);
    }
}
