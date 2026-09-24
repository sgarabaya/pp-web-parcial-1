# Patrones de diseño

Patrones identificados en el código, con referencias a los archivos de
`www/`. No hay framework: los patrones surgen del uso directo de PHP.

## Repository (Repositorio)

`repos/Repository.php` define un repositorio genérico abstracto sobre PDO;
`UserRepository`, `VehicleRepository` y `SaleRepository` lo concretan.

- Genérico con docblock `@template T`; provee operaciones CRUD
  (`findById`, `findAll`, `findBy`, `create`, `update` parcial, `delete`).
- Cada subclase aporta solo: tabla (`getTableName`), mapeo
  (`mapFrom`/`mapTo`) y columnas editables (`getColumns`).
- Aísla a las views/actions de SQL; el acceso a datos queda en una sola capa.

```php
class VehicleRepository extends Repository { /* solo configuración */ }
```

## Template Method

`Repository` fija el esqueleto de cada operación y deja los pasos variables
(`getTableName`, `mapFrom`, `mapTo`, `getColumns`) a las subclases. Ej.:
`update()` construye el `UPDATE ... SET` iterando las columnas de
`getColumns()`, sin que cada repositorio reimplemente la lógica.

## Factory (fábrica)

`models/User.php`:

- `User::create(...)` — named constructor con firma explícita.
- `User::mapFrom(array $row)` y `User::fromFields(...)` — eligen la subclase
  concreta según el rol:

```php
if ($role === "ADMIN") {
    return new Administrator(...);
}
return new Employee(...);
```

Las entidades siguen la convención `mapFrom`/`mapTo` definida en todas las
clases de dominio (ver [Modelos de Datos](Modelos%20de%20Datos.md#mapeo-objeto-relacional)).

## Strategy + polimorfismo para autorización

`User` declara `canSee(string $page)`, `canEdit(string $entity)` y
`satisfies(string $requiredRole)` como abstractas; cada subclase cambia el
comportamiento:

- `Administrator`: todo devuelve `true` (ve y edita todo; pasa toda guardia).
- `Employee`: compara ritmos por rol (`canEdit("STOCK")` solo si rol `STOCK`).

La lógica de negocio (navbar, guards, botones) llama al contrato de `User` sin
saber qué implementación concreta hay detrás. No existe una clase `Policy`
separada: la política vive en el propio modelo.

## Singleton (conexión)

`repos/Database.php` expone una única conexión PDO por request:

```php
private static ?PDO $connection = null;
public static function connect(): PDO   // crea una sola vez, reusa el resto
```

Atributos activados: `ERRMODE_EXCEPTION`, `FETCH_ASSOC` por defecto y
`EMULATE_PREPARES = false` (prepared statements reales). Se accede de forma
estática desde `Repository` y desde `MetricasDashboard` (recibe el PDO por
parámetro en este caso).

## Identity Map (por request)

`Auth::$user` cachea el `User` cargado de la DB la primera vez que se pide:

```php
if (self::$user === null && self::$userId) {
    self::$user = new UserRepository()->findById(self::$userId);
}
```

Evita repetir la consulta dentro de un mismo request (una sola por página aún
llamando `Auth::user()` en auth, navbar, views, etc.).

## Fluent Interface (Builder)

`utilities/Validator.php` arma validaciones encadenando llamadas que devuelven
`$this`:

```php
$validator->field("paid_amount")->is_required()->is_numeric();
```

Acumula errores por campo y expone `is_valid()` / `get_errors()` /
`get_errors_as_string()`.

## Módulos de utilidad estática (static holder)

`utilities/` usa clases abstractas con métodos estáticos como namespaces de
funciones:

- `Auth` — sesión, login, guardias.
- `Api` — helpers HTTP y de sesión (`get_request`, `redirect`, mensajes flash).
- `Config` — lectura de entorno (`DB_*`).
- `Crypto` — `uuid4()`, `passwordHash()`, `passwordVerify()`.
- `Messages` — catálogo central de textos de error/éxito.
- `MetricasDashboard` (en `models/`) — queries agregadas del dashboard,
  también estático.

Centraliza mensajes y evita strings mágicos esparcidos en actions/views.

## DTO / objetos de dominio anémicos

`models/Vehicle.php` y `models/Sale.php`: objetos con propiedades públicas y
constructores con promotion, sin lógica de negocio (salvo mapeo). `SaleView`
es un DTO de lectura. El comportamiento se concentra en repositorios
(`registerSale`) y en `User` (roles).

## Transaction Script

`SaleRepository::registerSale()` orquesta el caso de uso completo en un solo
método: valida existencia de usuario/vehículo, chequea stock, inserta la venta
y decrementa el stock dentro de `beginTransaction()`/`commit()`/`rollBack()`.
No hay Unit of Work ni ORM: la transacción se maneja explícitamente.

## Whitelist (partial update)

`Repository::update()` solo afecta columnas que existan en `getColumns()` de la
subclase. Impide que campos como `id` o `created` (no listados) se sobrescriban
desde `$_POST`.

## Post/Redirect/Get (PRG) + Flash messages

Las actions nunca renderizan la vista: redirigen siempre (`finally`) y el
resultado viaja en la sesión como mensaje flash:

```
action → API::set_message/set_error_message → header("Location: /views/...")
footer.php → renderiza toast y limpia $_SESSION["message*"]
```

Evita el re-envío del formulario al refrescar (F5) y muestra el resultado
exactamente una vez.

## Front Controller / Active Record: ausencias notables

- **No hay Front Controller**: cada página es un entrypoint PHP propio; la
  inicialización común está en `autoload.php`.
- **No hay Active Record**: los modelos no conocen la DB; el mapeo
  (`mapFrom`/`mapTo`) es simétrico y lo ejercen los repositorios.
- **No hay inyección de dependencias**: las dependencias se resuelven por
  construcción directa (`new SaleRepository()`) o por estáticos
  (`Database::connect()`); la unidad de composición es la request.