# Ciclo de vida de una request — funcionamiento interno

Esta página recorre qué pasa de verdad adentro del proceso PHP, desde que se pide una URL hasta que
se devuelve una página (o un redirect). Asume el mismo *cómo* de
[Arquitectura.md](Arquitectura.md#5-flujo-de-una-request-a-alto-nivel) pero va línea por línea.

---

## 1. Secuencia de arranque (en cada request)

El proyecto **no tiene un front controller real**; toda página y toda acción arranca igual:

```php
// p. ej. la primera línea de views/stock.php o de actions/sale.php
require_once "../autoload.php";
```

`www/autoload.php` hace dos cosas:

### 1a. Registra el autoloader

```php
spl_autoload_register(function ($class) {
    $directories = [__DIR__ . "/models", __DIR__ . "/repos",
                    __DIR__ . "/components", __DIR__ . "/utilities"];
    foreach ($directories as $directory) {
        $file = $directory . "/" . $class . ".php";
        if (file_exists($file)) { require_once $file; return; }
    }
});
```

Nombre de clase = nombre de archivo, buscado en `models/`, `repos/`, `components/`, `utilities/` en
ese orden. Así `new VehicleRepository()` auto-carga `repos/VehicleRepository.php`, que arrastra a
`Repository`, `Vehicle` y `Database` transitivamente. Nada de cadenas de `require_once` a mano —
salvo en el `tests/test_ventas.php`, que carga archivos explícitamente.

### 1b. Arranca la sesión y cachea al usuario actual

```php
Auth::load();
```

`Auth::load()` llama a `session_start()` y después copia los campos de usuario de la sesión a
propiedades estáticas, para que el resto de la request los lea barato:

```php
session_start();
self::$userId   = Api::safe_get($_SESSION, "user.id");
self::$userRole = Api::safe_get($_SESSION, "user.role");
self::$userName = Api::safe_get($_SESSION, "user.name");
```

Por eso `Auth::getUserId()`, `Auth::hasRole(...)`, etc. funcionan en todos lados sin volver a tocar
`$_SESSION`.

---

## 2. Flujo de autenticación

### Login (`/login.php`)

1. Si el visitante ya está logueado (`$_SESSION["user.id"]` seteado), redirect a `/index.php`.
2. En **GET**: renderiza el formulario de login.
3. En **POST** con `email` + `password`:

   ```php
   if (Auth::login()) {
       Api::redirect("/index.php");
   } else {
       Api::set_error_message(Messages::wrongLoginInfo());
       Api::redirect("/login.php");
   }
   ```

`Auth::login()`:

```php
$email = Api::safe_get($_POST, "email");      // acceso null-safe a $_POST
$password = Api::safe_get($_POST, "password");

$userRepo = new UserRepository();
$user = $userRepo->findByEmail($email);

if ($user && Crypto::passwordVerify($password, $user->getPasswordHash())) {
    $_SESSION["user.id"]   = $user->getId();
    $_SESSION["user.role"] = $user->getRole();
    $_SESSION["user.name"] = sprintf("%s.%s", substr($user->getName(), 0, 1), $user->getLastName());
    return true;
}
return false;
```

Notas:

- La búsqueda es una query parametrizada (`findBy("email", $email)` → `WHERE email = ?`), así que
  no hay inyección SQL por el formulario de login.
- Las contraseñas se verifican con `password_verify()` contra un hash **Argon2id**
  (`Crypto::passwordHash()` usa `PASSWORD_ARGON2ID`); la base solo guarda hashes — ver
  [Modelos de Datos.md](Modelos%20de%20Datos.md#users).
- Un intento fallido setea un flash genérico ("Datos incorrectos") vía PRG, así que la página de
  login muestra el toast después del redirect.

### Logout (`/logout.php`)

```php
session_start();
session_destroy();
header("Location: /login.php");
```

`logout.php` a propósito **no** incluye `autoload.php` — para este one-liner no hace falta nada más
pesado.

### Guards (chequeos de acceso)

Toda página protegida llama a uno de tres guards justo después de `autoload.php`:

| Guard | Comportamiento |
|-------|----------------|
| `Auth::ensureLoggedIn()` | Redirect a `/login.php` si faltan `user.id`/`user.role`. Cualquier rol logueado pasa. |
| `Auth::requireRole("STOCK" / "SALES" / "ADMIN")` | Sin login → `/login.php`; logueado con otro rol → `/index.php`; `ADMIN` pasa cualquier chequeo de rol; `"ANY"` significa "cualquier usuario logueado". |
| `canSee($page)` / `canEdit($obj)` | Chequeos no bloqueantes que se usan para *filtrar la UI* (entradas de la barra lateral, botones de acción). Ver [Autenticacion.md](Autenticacion.md). |

---

## 3. Una página de solo lectura: listar vehículos (`/views/stock.php`)

```
GET /views/stock.php
  ├─ require "../autoload.php"          → autoloader + Auth::load()
  ├─ Auth::ensureLoggedIn()             → /login.php si es anónimo
  ├─ require header.php / navbar.php    → esqueleto de la página; navbar("STOCK") renderiza la sidebar
  ├─ $vehiclesRepo = new VehicleRepository();
  ├─ foreach ($vehiclesRepo->findAll() as $vehicle)   ← lo único pesado
  │      → Repository::findAll() → SELECT * FROM Vehicles
  │      → Vehicle::mapFrom() por fila
  │      → render <tr> por vehículo
  └─ require "../components/footer.php" → toast flash + íconos lucide + </body>
```

Detalles notables de esta página:

- Guarda con `ensureLoggedIn()` ("no hay un rol mínimo acá") — cualquier empleado puede ver stock.
- El botón **"Agregar Vehiculo"** solo se renderiza si `Auth::canEdit("STOCK")` (ADMIN o STOCK), y
  los links de editar/eliminar por fila se gatean igual. Los links de venta usan
  `Auth::canEdit("SALES")`.
- Las filas con `stock == 0` reciben la clase CSS `error` (fila roja).
- La baja usa un `<dialog>` HTML de confirmación; el formulario de confirmación postea `id` +
  `METHOD=DELETE` a `/actions/stock.php`.

---

## 4. Una mutación: alta/modificación/baja de vehículo (`/actions/stock.php`)

La acción es un script que despacha. Forma general:

```
POST /actions/stock.php   (siempre POST; el verbo va en el campo oculto METHOD)
  ├─ require "../autoload.php"
  ├─ Auth::requireRole("STOCK")           → solo ADMIN o STOCK
  ├─ despacho según $_POST["METHOD"]:
  │     POST   → create()
  │     PUT    → update()
  │     DELETE → delete()
  ├─ cada rama:
  │     $data = Api::get_request();            // snapshot de $_POST
  │     if (empty($data)) throw ...            // guard de payload vacío
  │     $validator = validate($data); if (!$validator->is_valid()) throw ...
  │     $repo->create($vehicle) / $repo->update($id, $data) / $repo->delete($id)
  ├─ try / catch:
  │     éxito   → Api::set_message(operationSuccessful, "success")
  │     error   → Api::set_error_message($e->getMessage())
  └─ finally:

      Api::redirect("/views/stock.php");       // PRG — siempre redirige
```

`create()` arma un objeto de dominio a partir de la entrada validada:

```php
$vehicle = new Vehicle(
    id: Crypto::uuid4(),          // UUID v4 aleatorio (36 caracteres)
    brand: $data["brand"],
    model: $data["model"],
    price: $data["price"],
    stock: $data["stock"],
    year: $data["year"],
    created: new DateTimeImmutable("now"),
);
$vehicleRepository->create($vehicle);   // INSERT INTO Vehicles (…) VALUES (…) — parametrizado
```

`update()` le pasa el `$data` crudo a `Repository::update()`, que lo filtra por la whitelist de
`getColumns()` (`brand, model, year, price, stock` — así `id`/`created` nunca se pueden pisar).
`delete()` solo necesita el `id`.

Los errores nunca renderizan acá: se convierten en mensajes flash y el usuario vuelve al listado con
un toast.

---

## 5. Una mutación: registrar una venta (`/actions/sale.php`)

Es el flujo más interesante porque abarca **dos tablas** y tiene que quedar consistente.

```
POST /actions/sale.php
  ├─ Auth::requireRole("SALES")
  ├─ validate()  → vehicle_id/paid_amount/client_name/client_contact/payment_method requeridos
  ├─ arma Sale{ id: uuid4, userId: Auth::getUserId(), … }
  └─ SaleRepository::registerSale($sale)
```

`registerSale()` en `www/repos/SaleRepository.php`:

```php
public function registerSale(Sale $sale): void
{
    $userRepo    = new UserRepository();
    $vehicleRepo = new VehicleRepository();

    $user    = $userRepo->findById($sale->userId);
    $vehicle = $vehicleRepo->findById($sale->vehicleId);

    if (!$user)              throw new Exception(Messages::doesntExist("Empleado"));
    if (!$vehicle)           throw new Exception(Messages::doesntExist("Vehiculo"));
    if ($vehicle->stock === 0) throw new Exception(Messages::operationFailed());

    $conn = Database::connect();
    try {
        $conn->beginTransaction();
        $this->create($sale);                    // INSERT INTO Sales
        $vehicle->stock -= 1;
        $vehicleRepo->update($vehicle->id, $vehicle->mapTo());  // UPDATE Vehicles SET stock = ?
        $conn->commit();
    } catch (Exception $ex) {
        $conn->rollBack();
        throw $ex;
    }
}
```

Por qué importa:

- **El `userId` sale de la sesión** (`Auth::getUserId()`), nunca del formulario — no podés registrar
  una venta como si fuera otro.
- El descuento de stock se hace **en la misma transacción** que el insert de la venta. Si falla
  cualquiera de las dos, todo se revierte: no podés terminar con una venta cuyo vehículo no cambió
  de stock.
- El chequeo `stock === 0` es un guard barato *antes* de la transacción; después, la transacción
  hace el descuento a prueba de carreras.
- Se le pasa `$vehicle->mapTo()` a `update()`, pero la whitelist de columnas del repo solo deja
  pasar `brand, model, year, price, stock` — así `id`/`created` no se pueden alterar.
- Al éxito, la acción setea el flash y hace PRG a `/views/sales.php`, que lista las ventas con
  empleado/vehículo en texto vía `SaleRepository::fetchDetails()`. Además de los datos que ya
  mostraba, el listado suma una columna **"Fecha"** (`created`, formato `Y/m/d`) y viene ordenado por
  **fecha descendente** (`ORDER BY S.created DESC`), así la venta más reciente queda arriba.

No hay edición ni baja de ventas a propósito: una vez registrada, una venta es inmutable en esta
app.

---

## 6. El panel de control (`/index.php`)

```
GET /index.php
  ├─ autoload.php
  ├─ Auth::ensureLoggedIn()
  ├─ $db = Database::connect();
  ├─ MetricasDashboard::registrarVisualizacion();      // contador de sesión +1
  ├─ $es_admin = Auth::hasRole("ADMIN");
  ├─ $total_recaudado = MetricasDashboard::obtenerTotalRecaudado($db);          // SUM(paid_amount)
  ├─ $stock_actual    = MetricasDashboard::obtenerCantidadVehiculosDisponibles($db); // SUM(stock)
  ├─ $salesRepo = new SaleRepository();
  ├─ render header/nav + tarjetas (finanzas solo admin vs. accesos rápidos del empleado)
  │        + sección "Estadisticas" con <canvas id="sales-chart">
  └─ json_encode($salesRepo->fetchSalesOverview()) → loadSalesOverview() (public/charts.js)
```

`MetricasDashboard` es una clase de conveniencia con miembros estáticos. El contador de
visualizaciones ahora vive **en la sesión**, no en una propiedad estática:

```php
class MetricasDashboard
{
    // Contador de visualizaciones de la sesión
    public static function get_visualizaciones_sesion(): int
    {
        return $_SESSION["visualizaciones"];
    }
    public static function registrarVisualizacion(): void
    {
        if (!isset($_SESSION["visualizaciones"])) {
            $_SESSION["visualizaciones"] = 0;
        }
        $_SESSION["visualizaciones"] += 1;
    }

    // Calcula las ventas
    public static function obtenerTotalRecaudado(PDO $db): float { /* SELECT SUM(paid_amount) … */ }

    // Cuenta la cantidad de vehículos disponibles en stock
    public static function obtenerCantidadVehiculosDisponibles(PDO $db): int { /* SELECT SUM(stock) … */ }
}
```

Detalles:

- `registrarVisualizacion()` inicializa la key en `0` si no existe y después suma `+1`. Se llama una
  vez por request en `index.php`.
- `get_visualizaciones_sesion()` lee `$_SESSION["visualizaciones"]` y el panel lo muestra ("N
  visualizaciones de este panel durante su sesión").
- Como el contador queda en `$_SESSION`, **sí persiste entre requests de la misma sesión**: si
  entrás 5 veces al panel, te muestra 5. (Antes era una propiedad estática y, por el modelo de
  procesos de Apache prefork + mod_php, se reseteaba en cada request — ese bug ya está corregido.)
- El método usa el patrón *registrar antes de leer*: `index.php` siempre llama a
  `registrarVisualizacion()` antes de mostrar el valor, así la key siempre existe en el flujo real.
- Sigue satisfaciendo el requisito de la consigna de "métodos estáticos" (toda la clase es
  estática), y encima ahora el comportamiento es correcto.
- **Gráfico de ventas por empleado**: después de las tarjetas, el panel renderiza la sección
  "Estadisticas" con un `<canvas id="sales-chart">`. El JS carga **Chart.js desde el CDN** en
  runtime y `public/charts.js` (`loadSalesOverview()`) pinta un gráfico de torta. Los datos vienen de
  `SaleRepository::fetchSalesOverview()`, que agrega por empleado (`GROUP BY U.id`) el **total
  recaudado** (`SUM(S.paid_amount)`) y la **cantidad de ventas** (`COUNT(S.id)`); el pie muestra las
  dos series (ingresos en `$` y cantidad). Es visible para **cualquier rol logueado**, porque
  `index.php` solo exige `Auth::ensureLoggedIn()`.

---

## 7. Pipeline de renderizado (header → página → footer)

Cada página arma su layout a mano:

1. **`components/header.php`** — abre `<!DOCTYPE html>`, carga Google Fonts, `/public/style.css` y
   el favicon; termina con `<body>`. HTML puro, sin lógica.
2. **`components/navbar.php`** — define `navbar(string $selected)`; recorre el mapa de navegación y
   solo renderiza los links que el rol actual puede ver (`Auth::canSee()`); muestra el nombre del
   usuario logueado y el link de logout. El argumento `$selected` resalta la sección activa (p. ej.
   `navbar("STOCK")`).
3. El **cuerpo de la página** (`views/*`).
4. **`components/footer.php`** — consume el mensaje flash de sesión (de un solo uso):

   ```php
   $message = Api::safe_get($_SESSION, "message");
   $message_type = Api::safe_get($_SESSION, "message_type");
   $_SESSION["message"] = null;          // se consume: solo se muestra una vez
   $_SESSION["message_type"] = null;
   ```

   y después renderiza un toast `<div>` con barra de progreso CSS y un script de auto-cierre a los
   3 segundos, seguido del inicializador de íconos Lucide y `</body>`.

Este "renderizá la página vos mismo" es simple y trazable, a costa del guard/includes duplicados que
ves al tope de cada vista.

---

## 8. El ciclo completo de una mirada

| Paso | Código | Tipo |
|------|--------|------|
| Boot | `autoload.php` → autoloader + `Auth::load()` | cada request |
| Guard | `Auth::ensureLoggedIn()` / `requireRole()` | toda página/acción protegida |
| Lectura | `XxxRepository->findAll()/findById()/fetchDetails()` | vistas |
| Escritura | `actions/*.php` → `create()/update()/delete()/registerSale()` | vistas → formularios POST |
| Feedback | `Api::set_message()` / `set_error_message()` + redirect | PRG |
| Render | `footer.php` muestra el toast flash | siguiente GET |