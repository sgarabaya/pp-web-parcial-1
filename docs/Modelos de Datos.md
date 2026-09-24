# Modelo de datos

La base es **MySQL**, esquema `ruta9`, manejada enteramente por los archivos SQL de `migrations/` y
accedida a través de la capa PDO que se describe en [patterns.md](patterns.md#4-singleton--database).

---

## 1. Panorama de entidades y relaciones

```
┌────────────────────┐        ┌────────────────────┐        ┌────────────────────┐
│       Users        │        │      Vehicles      │        │       Sales        │
├────────────────────┤        ├────────────────────┤        ├────────────────────┤
│ id        VARCHAR(36) PK    │ id        VARCHAR(36) PK    │ id        VARCHAR(36) PK
│ name      VARCHAR(40)       │ brand     VARCHAR(40)       │ user_id   VARCHAR(36) FK ──► Users.id
│ last_name VARCHAR(40)       │ model     VARCHAR(40)       │ vehicle_id VARCHAR(36) FK ─► Vehicles.id
│ email     VARCHAR(40) UNIQUE│ year      INT               │ paid_amount DECIMAL(10,2)
│ password_hash VARCHAR(255)  │ price     DECIMAL(10,2)     │ client_name VARCHAR(40)
│ role      ENUM(…)           │ stock     INT               │ client_contact VARCHAR(40)
│ created   DATETIME          │ created   DATETIME          │ payment_method VARCHAR(40)
└────────────────────┘        └────────────────────┘        │ created   DATETIME
                                                            └────────────────────┘
```

Tres tablas y dos relaciones (`Sales.user_id → Users.id`, `Sales.vehicle_id → Vehicles.id`), las
dos con foreign keys. Toda primary key es un **UUID de 36 caracteres** generado en
`Crypto::uuid4()`, no un auto-increment entero.

---

## 2. Tablas

### Users

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | `VARCHAR(36)` | PK, UUID |
| `name` | `VARCHAR(40)` NOT NULL | |
| `last_name` | `VARCHAR(40)` NOT NULL | |
| `email` | `VARCHAR(40)` UNIQUE NOT NULL | Constraint único — no puede haber dos logins iguales |
| `password_hash` | `VARCHAR(255)` NOT NULL | Hash Argon2id (ver más abajo) |
| `role` | `ENUM('ADMIN','STOCK','SALES')` NOT NULL DEFAULT `'SALES'` | Control de acceso por roles |
| `created` | `DATETIME DEFAULT CURRENT_TIMESTAMP` | |

**Almacenamiento de contraseñas**: solo se guarda el hash Argon2id que produce `password_hash()`
(`Crypto::passwordHash`). La verificación se hace con `password_verify()` — la contraseña en texto
nunca toca la base. Los hashes precargados llevan un comentario `/* pwd:xxx */` para que las demos
puedan loguearse sin pasar el texto plano en otro archivo.

### Vehicles

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | `VARCHAR(36)` | PK, UUID |
| `brand` | `VARCHAR(40)` NOT NULL | |
| `model` | `VARCHAR(40)` NOT NULL | |
| `year` | `INT` NOT NULL | El formulario limita entre 1908–2026 |
| `price` | `DECIMAL(10,2)` NOT NULL | El dinero nunca se guarda como float |
| `stock` | `INT NOT NULL DEFAULT 0` | Unidades disponibles; se descuenta en cada venta |
| `created` | `DATETIME DEFAULT CURRENT_TIMESTAMP` | |

### Sales

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | `VARCHAR(36)` | PK, UUID |
| `user_id` | `VARCHAR(36)` NOT NULL | FK → `Users.id` (`fk_sale_user`) |
| `vehicle_id` | `VARCHAR(36)` NOT NULL | FK → `Vehicles.id` (`fk_sale_vehicle`) |
| `paid_amount` | `DECIMAL(10,2)` NOT NULL | Lo que pagó el cliente |
| `client_name` | `VARCHAR(40)` NOT NULL | |
| `client_contact` | `VARCHAR(40)` NOT NULL | Teléfono o email |
| `payment_method` | `VARCHAR(40)` NOT NULL | Uno de `CASH`, `FINANCED`, `EXCHANGE+CASH`, `EXCHANGE+FINANCED` (no hay `ENUM` en SQL; el conjunto válido lo definen el formulario y la función de mapeo de la app) |
| `created` | `DATETIME DEFAULT CURRENT_TIMESTAMP` | |

---

## 3. Migraciones y datos de demo

Docker Compose monta `./migrations` en `/docker-entrypoint-initdb.d` de la imagen de MySQL. Con un
**volumen de datos nuevo**, la imagen ejecuta los scripts en orden alfabético, así que:

### `0001_InitialMigration.sql` — esquema + admin

1. `CREATE DATABASE IF NOT EXISTS ruta9;` + `USE ruta9;` (también cubierto por la variable
   `MYSQL_DATABASE` del compose).
2. Crea las tres tablas (`CREATE TABLE IF NOT EXISTS`).
3. Seed del usuario **admin** con el UUID *fijo* `00000000-0000-0000-0000-000000000000`:

   | Email | Contraseña | Rol |
   |-------|------------|-----|
   | `admin@ruta9.ar` | `admin` | `ADMIN` |

   `INSERT IGNORE` significa que re-ejecutar el script no lo duplica.

### `DemoSeed.sql` — datos de demo

Agrega (todo `INSERT IGNORE` / idempotente):

- **4 empleados** con `UUID()` aleatorios:

  | Nombre | Email | Rol | Contraseña |
  |--------|-------|-----|------------|
  | Jorge Perez | `jorge.perez@ruta9.ar` | `STOCK` | `jorge` |
  | Florencia Flores | `florencia.flores@ruta9.ar` | `SALES` | `flor` |
  | Enzo Garcia | `enzo.garcia@ruta9.ar` | `SALES` | `enzo` |
  | Elva Bozzo | `elva.bozzo@ruta9.ar` | `SALES` | `elva` |

- **35 vehículos** de varias marcas (Toyota, Honda, Ford, Tesla, VW, Audi, BMW, …) con precios y
  stocks realistas. Dos de ellos (`Nissan Rogue`, `Toyota Highlander`) tienen `stock = 0` a
  propósito, para que se vea el estado "sin stock" en la UI.
- **40 ventas** generadas con una tabla temporal + `INSERT … SELECT … RAND()`: empleados de SALES
  random, vehículos random, `paid_amount` random entre 10 000 y 50 000, método de pago random y una
  lista de clientes/contactos verosímiles.

> **Re-aplicar migraciones**: como los scripts de init solo corren con un volumen de datos nuevo,
> cambiar el esquema más adelante implica recrear el volumen de MySQL, p. ej.
> `docker compose down -v && docker compose up --build`. No hay un runner de migraciones en la app.

---

## 4. Cómo mapea el código al esquema

| Nombre SQL (snake_case) | Modelo PHP | Mapeo |
|--------------------------|------------|-------|
| `Users` | `User` | `UserRepository` → `User::mapFrom()/mapTo()` |
| `Vehicles` | `Vehicle` | `VehicleRepository` → `Vehicle::mapFrom()/mapTo()` |
| `Sales` | `Sale` (+ read model `SaleView`) | `SaleRepository` → `Sale::mapFrom()/mapTo()`; `fetchDetails()` arma `SaleView` con el join de 3 tablas |

Detalles del mapeo:

- `mapFrom()` convierte filas `VARCHAR`/`DATETIME` en propiedades tipadas (`(int)`, `(float)`,
  `DateTimeImmutable`).
- `mapTo()` serializa de vuelta a arrays snake_case para `INSERT`/`UPDATE`.
- Los updates parciales se whitelistean por repo (`getColumns()`), así las columnas fijas
  (`id`, `created`, `password_hash` salvo manejo explícito) nunca entran en un update genérico.
  `UserRepository::update()` trata el caso especial de un `password` no vacío hasheándolo dentro de
  `password_hash` antes de delegar en el update genérico.

La página de listado de ventas (`views/sales.php`) renderiza filas de `SaleView`; el panel agrega
con `MetricasDashboard` (`SUM(paid_amount)`, `SUM(stock)`).