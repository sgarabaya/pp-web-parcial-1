# Patrones de diseño

Esta página profundiza en los patrones de diseño que usa el código. El resumen a alto nivel está en
[Arquitectura.md](Arquitectura.md#6-patrones-usados-de-una-mirada); acá los vemos uno por uno con
referencias concretas al código.

---

## 1. Patrón Repository

Todo el SQL vive en `www/repos/`. El resto de la aplicación nunca toca PDO directamente — le pide a
un repositorio objetos tipados (o le pasa objetos para persistirlos).

La clase base `www/repos/Repository.php` define el contrato CRUD genérico con métodos abstractos:

```php
abstract class Repository
{
    abstract protected function getTableName(): string; // p. ej. "Users"
    abstract protected function mapFrom(array $row): ?object; // fila DB → objeto de dominio
    abstract protected function mapTo(object $obj): array;    // objeto de dominio → fila DB
    abstract protected function getColumns(): array;          // whitelist para updates parciales

    public function findById(string $id): ?object { /* SELECT ... WHERE id = ? LIMIT 1 */ }
    public function findAll(): array           { /* SELECT * FROM <tabla> */ }
    public function findBy(string $column, mixed $value): array { /* SELECT * WHERE col = ? */ }
    public function create(object $obj): bool  { /* INSERT con placeholders */ }
    public function update(string $id, array $partialData): bool { /* UPDATE con whitelist */ }
    public function delete(string $id): bool   { /* DELETE WHERE id = ? */ }
}
```

Un repositorio concreto solo aporta las cuatro piezas abstractas:

```php
/** @extends Repository<Vehicle> */
class VehicleRepository extends Repository
{
    protected function getTableName(): string { return "Vehicles"; }
    protected function getColumns(): array    { return ["brand", "model", "year", "price", "stock"]; }
    protected function mapFrom(array $row): ?object { return Vehicle::mapFrom($row); }
    protected function mapTo(object $obj): array    { return $obj->mapTo(); }
}
```

### Beneficios en este código

- **Un solo lugar para cambios de esquema.** Si se renombra una columna, solo cambia el mapeo/lista
  de columnas del repositorio.
- **API tipada para los consumidores.** Las vistas reciben objetos `Vehicle`, no arrays crudos.
- **CRUD reutilizable.** `UserRepository`, `VehicleRepository` y `SaleRepository` comparten todo el
  CRUD genérico; cada uno le agrega solo su comportamiento:
  - `UserRepository::findByEmail()` envuelve `findBy("email", $email)`.
  - `UserRepository::update()` hashea el campo `password` antes de delegar en el padre.
  - `SaleRepository` agrega las queries de dominio: `registerSale()` (transacción venta + descuento
    de stock), `fetchDetails()` (listado con joins) y `fetchSalesOverview()` (agregación por empleado
    para el gráfico del panel).

---

## 2. Template Method (método plantilla) en la base `Repository`

Los algoritmos genéricos de `Repository` son métodos plantilla: el *esqueleto* de, por ejemplo,
`update()` está fijo, pero los *pasos* se delegan a las subclases.

`update()` es el mejor ejemplo.:

1. rechaza payloads vacíos,
2. carga la fila con `findById()` (si no existe, corta),
3. filtra `$partialData` por la whitelist `getColumns()` que da la subclase (así los callers no
   pueden pisar `id`, `created` ni ninguna columna que el dominio no permita),
4. arma `SET col = ?, ...` con las keys que sobrevivieron y ejecuta con el id al final.

```php
public function update(string $id, array $partialData): bool
{
    if (empty($partialData)) return false;
    $el = $this->findById($id);
    if (!$el) return false;

    $data = [];
    foreach ($partialData as $key => $value) {
        if (in_array($key, $this->getColumns())) $data[$key] = $value;
    }
    // UPDATE <tabla> SET k1 = ?, k2 = ? WHERE id = ?
}
```

Las subclases también pueden *override* del template y aumentarlo, como hace
`UserRepository::update()` con el hasheo de la contraseña:

```php
public function update(string $id, array $partialData): bool
{
    if (isset($partialData["password"]) && !empty($partialData["password"])) {
        $partialData["password_hash"] = Crypto::passwordHash($partialData["password"]);
        unset($partialData["password"]);
    }
    return parent::update($id, $partialData);
}
```

Un campo de contraseña vacío, entonces, deja el hash existente intacto.

---

## 3. Mapeo de modelos: `mapFrom()` / `mapTo()`

Los modelos de dominio (`User`, `Vehicle`, `Sale`) son objetos pelados que saben convertir entre
una **fila de la base** (keys en snake_case) y **propiedades tipadas** (camelCase), y de vuelta:

```php
class Vehicle
{
    public static function mapFrom(array $data): self { /* fila DB → objeto */ }
    public function mapTo(): array { /* objeto → fila DB */ }
}
```

Es un enfoque estilo data mapper: el repo es dueño del SQL, el modelo es dueño de la conversión de
forma. También convierte tipos nativos al entrar (`(int)`, `(float)`, `DateTimeImmutable`) y los
normaliza de vuelta al ir a la base.

`User` es el caso interesante: es la **base abstracta** de la herencia pedida por la consigna
(`Usuario → Empleado, Administrador`). Comparte con sus subclases el constructor, los getters/setters
y `mapTo()`, pero `mapFrom()` / `create()` son **fábricas que despachan por rol**:

```php
abstract class User
{
    public function __construct(
        protected string $id,
        protected string $name,
        protected string $lastName,
        protected string $email,
        protected string $passwordHash,
        protected string $role,
        protected DateTimeImmutable $created,
    ) {
        $this->assertValidRole($role); // hook polimórfico
    }

    abstract public function canSee(string $page): bool;     // matriz de la navbar
    abstract public function canEdit(string $entity): bool;  // matriz de los botones

    public function satisfies(string $requiredRole): bool {
        return $this->role === $requiredRole; // Empleado la hereda; Administrator la sobreescribe a true
    }

    public static function mapFrom(array $data): self
    {
        return match ($data["role"]) {
            "ADMIN" => new Administrator(id: $data["id"], /* ... */),
            default => new Employee(id: $data["id"], /* ... */),
        };
    }
    // + User::create(...) con el mismo dispatch, para el alta de usuarios
}
```

La fábrica elige la subclase según el `role` que viene de la base, y cada subclase impone su propio
invariante vía el hook `assertValidRole()`, que lanza `InvalidArgumentException` con
`Messages::wrongRole()` si el rol no corresponde:

- **`Employee`** — no puede ser `ADMIN`; `canSee()`/`canEdit()` codifican la matriz de permisos del
  empleado según `STOCK`/`SALES`.
- **`Administrator`** — exige `ADMIN`; `canSee()`/`canEdit()`/`satisfies()` devuelven `true` siempre
  (el admin es superconjunto).

Así la persistencia no cambió: la tabla `Users` sigue teniendo el `ENUM('ADMIN','STOCK','SALES')`,
la herencia vive solo en el modelo, y `UserRepository` sigue devolviendo `User` (la subclase concreta)
sin tocar el CRUD genérico de `Repository`. Son `Auth::requireRole()`, `canSee()` y `canEdit()` los
que delegan las decisiones de permiso en el objeto (`User::satisfies()`, `canSee()`, `canEdit()`).

El que `User` use `protected` + getters/setters mientras `Vehicle`/`Sale` usan propiedades
promovidas `public` es una inconsistencia menor del código; los dos estilos cumplen el requisito de
encapsulamiento en distinto grado (la clase `User` es el ejemplo más explícito del estilo
getter/setter que pedía la consigna).

---

## 4. Singleton — `Database`

`www/repos/Database.php` garantiza una sola conexión PDO por request, creada de forma perezosa:

```php
class Database
{
    private static ?PDO $connection = null;

    public static function connect(): PDO
    {
        if (self::$connection === null) {
            self::$connection = new PDO($dsn, Config::getDbUser(), Config::getDbPwd(), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$connection;
    }
}
```

Las opciones de conexión son deliberadas:

- `ERRMODE_EXCEPTION` — toda query fallida lanza excepción; los `try/catch` de las acciones dependen
  de esto.
- `FETCH_ASSOC` — las filas vuelven como arrays asociativos, que es lo que espera `mapFrom()`.
- `EMULATE_PREPARES = false` — prepared statements reales, con el manejo de tipos nativo de MySQL.

---

## 5. Clases de servicios estáticas (fachada)

Un grupo de clases abstractas en `www/utilities/` provee helpers sin estado que se usan en toda la
app:

| Clase | Responsabilidad | Métodos notables |
|-------|-----------------|------------------|
| `Config` | Acceso a variables de entorno (sin credenciales hardcodeadas) | `getDbHost()`, `getDbName()`, `getDbUser()`, `getDbPwd()` |
| `Auth` | Boot de sesión, login, guards de rol, usuario actual en caché | `load()`, `login()`, `user()`, `ensureLoggedIn()`, `requireRole()`, `canSee()`, `canEdit()`, `hasRole()` |
| `Api` | Helpers de request + mensajes flash + redirects | `get_request()`, `get_query_param()`, `safe_get()`, `set_message()`, `set_error_message()`, `redirect()` |
| `Crypto` | IDs y hasheo de contraseñas | `uuid4()`, `passwordHash()` (Argon2id), `passwordVerify()` |
| `Validator` | Validación de entrada fluida | `field()`, `is_required()`, `is_email()`, `is_numeric()`, `is_int()`, `has_max_length()`, `custom()`, `is_valid()`, `get_errors()` |
| `Messages` | Strings centralizados (en español) para el usuario | `operationSuccessful()`, `operationFailed()`, `doesntExist()`, `missingParameter()`, `wrongLoginInfo()`, `wrongRole()` … |

El idiom de clase abstracta con miembros estáticos mantiene estos namespaces sin posibilidad de
instanciarse y funciona como una "fachada" sobre los globals de PHP (`$_SESSION`, `$_POST`,
`$_GET`, `getenv`). El comportamiento completo de `Auth` está en
[Autenticacion.md](Autenticacion.md).

---

## 6. Interfaz fluida — `Validator`

`Validator` es un mini DSL de validación fluida. Cada regla devuelve `$this`, así que se encadenan:

```php
$validator = new Validator($data);
$validator
    ->field("email")->is_required()->is_email()->has_max_length(40)
    ->field("role")
        ->custom(fn($v) => in_array($v, ["ADMIN", "STOCK", "SALES"]), Messages::roleCanBe());

if (!$validator->is_valid()) {
    throw new Exception($validator->get_errors_as_string());
}
```

Los errores se acumulan por campo (`$errors["field"][] = "..."`) y se pueden mostrar de a uno o como
string completo. El hook `custom(callable $fn, string $message)` cubre cualquier cosa que las reglas
de fábrica no cubran.

---

## 7. Value object / read model — `SaleView`

`SaleView` es un snapshot de solo lectura de la query de ventas *con joins*
(`SaleRepository::fetchDetails()`), que combina datos de `Sales`, `Users` y `Vehicles`:

```php
class SaleView
{
    public function __construct(
        public string $id,
        public string $user,        // CONCAT(U.name, ' ', U.last_name)
        public string $vehicle,     // CONCAT(V.brand, ' ', V.model, ' (', V.year, ')')
        public float $paidAmount,
        public float $suggestedPrice, // V.price
        public string $clientName,
        public string $clientContact,
        public string $paymentMethod,
        public DateTimeImmutable $created,
    ) {}
}
```

A propósito **no** es un `Sale` — una fila de venta sola no alcanza para mostrar el empleado y el
vehículo en texto dentro del listado. Tiene `mapFrom()` pero no `mapTo()`, porque nunca se escribe
de vuelta a la base.

```sql
SELECT S.id, CONCAT(U.name,' ',U.last_name) AS `user`,
       CONCAT(V.brand,' ',V.model,' (',V.year,')') AS `vehicle`,
       S.paid_amount, V.price AS `suggested_price`,
       S.client_name, S.client_contact, S.payment_method, S.created
FROM Sales S
INNER JOIN Users U ON U.id = S.user_id
INNER JOIN Vehicles V ON V.id = S.vehicle_id
ORDER BY S.created DESC;
```

---

## 8. Post/Redirect/Get (PRG) + mensajes flash

Toda mutación (`actions/*.php`) sigue el patrón PRG:

1. Llega un **POST** con los datos del formulario.
2. La acción valida y hace la mutación (o tira una excepción).
3. El resultado se guarda como **mensaje flash** en `$_SESSION` (`Api::set_message(...)` /
   `Api::set_error_message(...)`).
4. La acción **redirige** (302) de vuelta a la vista de listado — nunca renderiza nada ella misma.

```php
try {
    // ...validar + mutar...
    Api::set_message(Messages::operationSuccessful(), "success");
} catch (Exception $e) {
    Api::set_error_message($e->getMessage());
} finally {
    Api::redirect("/views/stock.php");
}
```

El mensaje flash sobrevive al redirect porque vive en la sesión, y `components/footer.php` lo
consume exactamente una vez — lee `$_SESSION["message"]`/`["message_type"]`, **los limpia** y
renderiza un toast CSS que se auto-cierra a los 3 segundos. Como se consume en el footer, aparece
en cualquier página a la que haya caído el redirect.

Beneficios: refrescar la página después de un submit ya no reenvía el formulario, y los mensajes de
error sobreviven al paso de navegación.

---

## 9. Emulación de verbos HTTP con un campo `METHOD` oculto

Los formularios HTML solo soportan GET y POST, así que las acciones emulan PUT/DELETE. Cada
formulario de mutación lleva un campo oculto:

```html
<input type="text" name="METHOD" value="PUT" class="hidden" />
```

y la acción despacha según ese campo:

```php
if ($_SERVER["REQUEST_METHOD"] !== "POST") throw new Exception(Messages::operationFailed());
$method = Api::safe_get($_POST, "METHOD"); // "POST" | "PUT" | "DELETE"
switch ($method) {
    case "POST":   create(); break;
    case "PUT":    update(); break;
    case "DELETE": delete(); break;
}
```

Esto le da a las acciones un vocabulario tipo REST (`POST`/`PUT`/`DELETE` sobre
`/actions/stock.php`) encima de formularios HTML pelados — el comentario en el código aclara que es
un workaround porque HTML no tiene métodos de formulario para eso.

---

## 10. Arreglo estilo MVC

No hay framework — los roles de MVC los juegan archivos pelados:

- **Model** → `models/` (objetos de dominio) + `repos/` (persistencia)
- **Controller** → `actions/*.php` (mutaciones) más la lógica de guard al tope de cada vista
- **View** → `views/*.php` (páginas), compuestas con partials de `components/`

Los "controladores" son deliberadamente delgados: validar → llamar al repo → flash → redirect.
Tenerlos como scripts independientes (en vez de un front controller único) hace que el flujo de cada
página sea fácil de seguir, a costa de repetición (cada vista repite el `require_once
"../autoload.php"` + el guard `Auth::*` + los includes de header/nav/footer).