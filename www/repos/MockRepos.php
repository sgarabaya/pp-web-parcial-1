// <?php
// declare(strict_types=1);

// class VehicleRepository extends AbstractRepository
// {
//     protected function getTableName(): string
//     {
//         return "Vehicles";
//     }
//     protected function mapRow(array $row): Vehicle
//     {
//         return Vehicle::fromArray($row);
//     }

//     public function findById(string $id): ?Vehicle
//     {
//         $stmt = $this->pdo->prepare("SELECT * FROM Vehicles WHERE id = ?");
//         $stmt->execute([$id]);
//         $row = $stmt->fetch(PDO::FETCH_ASSOC);
//         return $row ? $this->mapRow($row) : null;
//     }

//     public function findAll(): array
//     {
//         $stmt = $this->pdo->query("SELECT * FROM Vehicles");
//         return array_map([$this, "mapRow"], $stmt->fetchAll(PDO::FETCH_ASSOC));
//     }

//     public function create(Vehicle $vehicle): bool
//     {
//         $sql = "INSERT INTO Vehicles (id, brand, model, year, price, stock, created)
//                 VALUES (?, ?, ?, ?, ?, ?, ?)";
//         return $this->pdo
//             ->prepare($sql)
//             ->execute([
//                 $vehicle->id,
//                 $vehicle->brand,
//                 $vehicle->model,
//                 $vehicle->year,
//                 $vehicle->price,
//                 $vehicle->stock,
//                 $vehicle->created->format("Y-m-d H:i:s"),
//             ]);
//     }

//     public function update(Vehicle $vehicle): bool
//     {
//         $sql =
//             "UPDATE Vehicles SET brand = ?, model = ?, year = ?, price = ?, stock = ? WHERE id = ?";
//         return $this->pdo
//             ->prepare($sql)
//             ->execute([
//                 $vehicle->brand,
//                 $vehicle->model,
//                 $vehicle->year,
//                 $vehicle->price,
//                 $vehicle->stock,
//                 $vehicle->id,
//             ]);
//     }
// }

// /**
//  * VEHICLE IMAGE REPOSITORY
//  */
// class VehicleImageRepository extends AbstractRepository
// {
//     protected function getTableName(): string
//     {
//         return "VehicleImages";
//     }
//     protected function mapRow(array $row): VehicleImage
//     {
//         return VehicleImage::fromArray($row);
//     }

//     public function findById(string $id): ?VehicleImage
//     {
//         $stmt = $this->pdo->prepare("SELECT * FROM VehicleImages WHERE id = ?");
//         $stmt->execute([$id]);
//         $row = $stmt->fetch(PDO::FETCH_ASSOC);
//         return $row ? $this->mapRow($row) : null;
//     }

//     public function findAll(): array
//     {
//         $stmt = $this->pdo->query("SELECT * FROM VehicleImages");
//         return array_map([$this, "mapRow"], $stmt->fetchAll(PDO::FETCH_ASSOC));
//     }

//     public function create(VehicleImage $image): bool
//     {
//         $stmt = $this->pdo->prepare(
//             "INSERT INTO VehicleImages (id, vehicle_id, url) VALUES (?, ?, ?)",
//         );
//         return $stmt->execute([$image->id, $image->vehicleId, $image->url]);
//     }
// }

// /**
//  * SALE REPOSITORY
//  */
// class SaleRepository extends AbstractRepository
// {
//     protected function getTableName(): string
//     {
//         return "Sales";
//     }
//     protected function mapRow(array $row): Sale
//     {
//         return Sale::fromArray($row);
//     }

//     public function findById(string $id): ?Sale
//     {
//         $stmt = $this->pdo->prepare("SELECT * FROM Sales WHERE id = ?");
//         $stmt->execute([$id]);
//         $row = $stmt->fetch(PDO::FETCH_ASSOC);
//         return $row ? $this->mapRow($row) : null;
//     }

//     public function findAll(): array
//     {
//         $stmt = $this->pdo->query("SELECT * FROM Sales");
//         return array_map([$this, "mapRow"], $stmt->fetchAll(PDO::FETCH_ASSOC));
//     }

//     public function create(Sale $sale): bool
//     {
//         $sql =
//             "INSERT INTO Sales (id, employee_id, vehicle_id, paid_amount, created) VALUES (?, ?, ?, ?, ?)";
//         return $this->pdo
//             ->prepare($sql)
//             ->execute([
//                 $sale->id,
//                 $sale->employeeId,
//                 $sale->vehicleId,
//                 $sale->paidAmount,
//                 $sale->created->format("Y-m-d H:i:s"),
//             ]);
//     }
// }
