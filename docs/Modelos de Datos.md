# Modelos de datos

Base: **MySQL** (esquema en `migrations/0001_InitialMigration.sql`). Todas las
tablas usan `VARCHAR(36)` como PK (UUID v4 generado en la aplicación) y
`created DATETIME DEFAULT CURRENT_TIMESTAMP`. Hay dos migraciones:
`0001_InitialMigration.sql` (esquema + admin) y `DemoSeed.sql` (datos demo),
ejecutadas en orden alfabético al inicializar el contenedor de la base.

## Esquema relacional

```
┌────────────┐  1        N  ┌────────────┐
│   Users    │◄─────────────│   Sales    │
│  id (PK)   │              │  id (PK)   │
│  name      │              │ user_id (FK) ──► Users.id
│  last_name │              │ vehicle_id(FK) ─► Vehicles.id
│  email (UQ)│              │ paid_amount
│  password_hash            │ client_name
│  role (ENUM)              │ client_contact
│  created   │              │ payment_method
└────────────┘              │ created
                            └────────────┘
┌────────────┐  1        N  ▲
│  Vehicles  │◄─────────────┘
│  id (PK)   │
│  brand     │
│  model     │
│  year      │
│  price     │
│  stock     │
│  created   │
└────────────┘
```

### `Users`

| Columna         | Tipo                         | Notas |
| --------------- | ---------------------------- | ----- |
| `id`            | VARCHAR(36) PK               | UUID v4 |
| `name`          | VARCHAR(40) NOT NULL         | |
| `last_name`     | VARCHAR(40) NOT NULL         | |
| `email`         | VARCHAR(40) UNIQUE NOT NULL  | login |
| `password_hash` | VARCHAR(255) NOT NULL        | Argon2id |
| `role`          | ENUM('ADMIN','STOCK','SALES') NOT NULL DEFAULT 'SALES' | |
| `created`       | DATETIME DEFAULT CURRENT_TIMESTAMP | |

**Clases**: `User` (abstracta) con subclases `Administrator` y `Employee`.
`User::fromFields()` despacha por `role` (factory): `ADMIN` → `Administrator`,
cualquier otro → `Employee`. El rol se valida en el constructor y en el setter
(`assertValidRole()`).

| Rol    | Clase            | `assertValidRole`                         |
| ------ | ---------------- | ----------------------------------------- |
| ADMIN  | `Administrator`  | rechaza cualquier rol ≠ `ADMIN`           |
| STOCK  | `Employee`       | rechaza `ADMIN` (acepta STOCK y SALES)    |
| SALES  | `Employee`       | rechaza `ADMIN` (acepta STOCK y SALES)    |

### `Vehicles`

| Columna | Tipo                   | Notas |
| ------- | ---------------------- | ----- |
| `id`    | VARCHAR(36) PK         | UUID v4 |
| `brand` | VARCHAR(40) NOT NULL   | |
| `model` | VARCHAR(40) NOT NULL   | |
| `year`  | INT NOT NULL           | |
| `price` | DECIMAL(10,2) NOT NULL | |
| `stock` | INT NOT NULL DEFAULT 0 | unidades disponibles |
| `created` | DATETIME DEFAULT CURRENT_TIMESTAMP | |

**Clase**: `Vehicle` (propiedades públicas tipadas; `year` → `int`, `price` →
`float`, `stock` → `int`).

### `Sales`

| Columna          | Tipo                   | Notas |
| ---------------- | ---------------------- | ----- |
| `id`             | VARCHAR(36) PK         | UUID v4 |
| `user_id`        | VARCHAR(36) NOT NULL   | FK → `Users(id)`, vendedor |
| `vehicle_id`     | VARCHAR(36) NOT NULL   | FK → `Vehicles(id)` |
| `paid_amount`    | DECIMAL(10,2) NOT NULL | monto efectivamente cobrado |
| `client_name`    | VARCHAR(40) NOT NULL   | |
| `client_contact` | VARCHAR(40) NOT NULL   | |
| `payment_method` | VARCHAR(40) NOT NULL   | `CASH`, `FINANCED`, `EXCHANGE+CASH`, `EXCHANGE+FINANCED` |
| `created`        | DATETIME DEFAULT CURRENT_TIMESTAMP | |

FKs con `CONSTRAINT fk_sale_user`/`fk_sale_vehicle`. No hay `ON DELETE`:
borrar un usuario/vehículo con ventas hará fallar el FK (los deletes de
`Repository::delete()` no contemplan esto).

**Clase**: `Sale`. **Modelo de lectura**: `SaleView` (usada para listar:
`user` y `vehicle` llegan concatenados del JOIN; agrega `suggestedPrice` =
precio de lista del vehículo, para comparar contra `paid_amount`).

### `payment_method` (valores libres en DB)

| Valor             | Etiqueta en UI (`views/sales.php`) |
| ----------------- | ---------------------------------- |
| `CASH`            | Efectivo                           |
| `FINANCED`        | Financiado                         |
| `EXCHANGE+CASH`   | Canje y Efectivo                   |
| `EXCHANGE+FINANCED` | Canje y Financiado                |

El campo se valida como requerido pero sus valores no se restringen en el
backend (`Validator` no valida contra el conjunto; la UI usa `<select>`).

## Mapeo objeto-relacional

Cada entidad implementa dos métodos estáticos/instancia que definen la
conversión entre fila y objeto:

- **`mapFrom(array $row): self`** — fila de la DB → objeto de dominio. Aplica
  coerciones: `(int)` para `year`/`stock`, `(float)` para `price`/`paidAmount`
  y `new DateTimeImmutable()` para `created`.
- **`mapTo(): array`** — objeto → fila. Serializa `created` en formato `ATOM`
  (ej. `2026-09-24T12:00:00+00:00`), compatible con `DATETIME` de MySQL dada la
  zona horaria por defecto del servidor.

| Clase          | mapFrom (DB)                                  | mapTo (fila)                                  |
| -------------- | --------------------------------------------- | --------------------------------------------- |
| `User` (y subs.) | `id, name, last_name, email, password_hash, role, created` | columnas homónimas + `created` ATOM; despacha la subclase por rol |
| `Vehicle`      | `id, brand, model, year(int), price(float), stock(int), created` | idem |
| `Sale`         | `id, user_id, vehicle_id, paid_amount(float), client_name, client_contact, payment_method, created` | idem |
| `SaleView`     | `id, user, vehicle, paid_amount, suggested_price, client_name, client_contact, payment_method, created` | — (solo lectura) |

`SaleView::mapFrom` espera los alias del JOIN de `SaleRepository::fetchDetails()`
(`user`, `vehicle`, `suggested_price`).

## Capa de repositorios

| Repositorio           | Tabla      | `getColumns()` (partial update)                    |
| --------------------- | ---------- | -------------------------------------------------- |
| `UserRepository`      | `Users`    | `name, last_name, email, password_hash, role`      |
| `VehicleRepository`   | `Vehicles` | `brand, model, year, price, stock`                 |
| `SaleRepository`      | `Sales`    | `user_id, vehicle_id, paid_amount, client_name, client_contact, payment_method` |

- `Repository` (abstracto genérico) provee `findById`, `findAll`, `findBy`,
  `create`, `update` (parcial por whitelist de `getColumns()`), `delete`.
- `UserRepository::update()` override: si llega `password`, lo hashea a
  `password_hash` y descarta el campo original.
- `SaleRepository` agrega queries ad-hoc:
  - `registerSale(Sale $sale)`: reglas de dominio + transacción (ver
    [Ciclo de Vida](Ciclo%20de%20Vida.md#registro-de-venta-salephp--salerepositoryregistersale)).
  - `fetchDetails(): SaleView[]`: `INNER JOIN` de `Sales` con `Users` y
    `Vehicles`, ordenado por fecha descendente.
  - `fetchSalesOverview()`: lista plana de ventas (sin agrupar) con JOIN a
    usuarios y vehículos. Columnas: `id`, `employee` (nombre y apellido),
    `paidAmount`, `vehicle` (marca + modelo + año), `vehicleStock` (stock
    actual del vehículo) y `created` como mes `YYYY-MM` (vía `DATE_FORMAT`),
    ordenada por fecha descendente. La agrupación y el cálculo (por mes,
    empleado y vehículo) se hacen en el cliente (`charts.js`).

## Queries agregadas del dashboard

`MetricasDashboard` (estático, recibe `PDO` por parámetro):

| Método                              | Consulta                                              |
| ----------------------------------- | ----------------------------------------------------- |
| `obtenerTotalRecaudado(PDO)`        | `SELECT SUM(paid_amount) FROM Sales` (todo el histórico) |
| `obtenerCantidadVehiculosDisponibles(PDO)` | `SELECT SUM(stock) FROM Vehicles` (suma de unidades, no de filas) |
| `get_visualizaciones_sesion()`      | contador en `$_SESSION["visualizaciones"]` (sin DB)   |

## Notas

- **IDs**: la PK se genera en PHP (`Crypto::uuid4()`), nunca con
  `UUID()` server-side salvo en `DemoSeed.sql`.
- `email` es único; intentar crear un duplicado falla con excepción de
  integridad que se traduce al mensaje flash genérico.
- `User::mapFrom()` es el único que despacha a subclases; `UserRepository`
  siembre devuelve `User` (por eso el `@extends Repository<User>`).
- El campo `stock` de `Vehicles` se decrementa al registrar una venta dentro
  de la misma transacción; no hay trigger ni columna de auditoría de
  movimientos de stock.