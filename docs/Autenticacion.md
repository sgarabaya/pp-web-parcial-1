# Autenticación y control de acceso por roles

La autenticación usa **sesiones de PHP**; la autorización es un sistema de **control de acceso por
roles (RBAC)** con tres roles. Todo vive en `www/utilities/Auth.php`, una clase estática (no
instanciable).

---

## 1. Estado de sesión

`Auth::load()` — llamado por `autoload.php` en **cada** request — arranca la sesión y cachea la
identidad del usuario actual en propiedades estáticas:

```php
session_start();
self::$userId   = Api::safe_get($_SESSION, "user.id");
self::$userRole = Api::safe_get($_SESSION, "user.role");
self::$userName = Api::safe_get($_SESSION, "user.name");
```

| Key de sesión | Contenido | Se cachea como |
|---------------|-----------|----------------|
| `user.id`   | UUID del usuario | `Auth::$userId` → `Auth::getUserId()` |
| `user.role` | `ADMIN` \| `STOCK` \| `SALES` | `Auth::$userRole` → `Auth::hasRole()`, guards |
| `user.name` | `"<primera-letra>.<apellido>"` (p. ej. `F.Flores`) | `Auth::$userName` → `Auth::getName()` |

El `user.name` de la sesión se arma al momento del login: `substr($user->getName(), 0, 1) . "." .
$user->getLastName()`. Solo se usa para el saludo de la barra lateral.

---

## 2. Roles

Definidos por el `ENUM` de `Users.role` (ver [Modelos de Datos.md](Modelos%20de%20Datos.md#users)):

| Rol | Significado |
|-----|-------------|
| `ADMIN` | Acceso total: gestión de usuarios + todo lo demás |
| `STOCK` | Inventario: ver y editar vehículos (alta / modificación / baja) |
| `SALES` | Ventas: ver y registrar ventas, ver el stock |

El rol `ADMIN` es un **superconjunto**: todos los chequeos de rol pasan automáticamente para el
admin.

---

## 3. La API de `Auth`

| Método | Propósito | Redirect ante fallo |
|--------|-----------|---------------------|
| `load()` | Arranca la sesión + cachea al usuario actual (lo llama `autoload.php`) | — |
| `login(): bool` | Valida email/contraseña contra la base y puebla la sesión | — |
| `ensureLoggedIn()` | Exige *cualquier* usuario logueado | `/login.php` |
| `user()` | Hidrata y cachea el objeto `User` actual (una query por request); las decisiones de permiso se delegan en él | — |
| `requireRole(string $role)` | Exige un rol específico; delega en `User::satisfies()`; acepta `"ANY"` para "cualquier usuario logueado" | `/login.php` si es anónimo, `/index.php` si es otro rol |
| `canSee(string $page)` | No bloqueante: ¿este rol puede ver esta sección del nav? delega en `User::canSee()` | filtra la barra lateral |
| `canEdit(string $obj)` | No bloqueante: ¿este rol puede hacer las acciones de esta entidad? delega en `User::canEdit()` | filtra botones |
| `hasRole(string $role)` | Comparación simple (se usa para UI según rol, p. ej. la tarjeta de finanzas del admin) | — |
| `getName()` / `getUserId()` | Accesors del usuario en caché | — |

---

## 4. Matriz de guards

### Guards a nivel de ruta (aplicación dura)

Toda página/acción exige su rol mínimo antes de hacer nada:

| Página | Guard | Quién entra |
|--------|-------|-------------|
| `index.php` (panel) | `Auth::ensureLoggedIn()` | cualquier usuario logueado |
| `views/stock.php` | `Auth::ensureLoggedIn()` | cualquier usuario logueado (solo lectura) |
| `views/create_sale.php` | `Auth::ensureLoggedIn()` | cualquier usuario logueado |
| `actions/stock.php` (alta/modificación/baja) | `Auth::requireRole("STOCK")` | `ADMIN`, `STOCK` |
| `views/edit_stock.php` | `Auth::requireRole("STOCK")` | `ADMIN`, `STOCK` |
| `views/sales.php` | `Auth::requireRole("SALES")` | `ADMIN`, `SALES` |
| `actions/sale.php` | `Auth::requireRole("SALES")` | `ADMIN`, `SALES` |
| `views/users.php` | `Auth::requireRole("ADMIN")` | solo `ADMIN` |
| `views/edit_user.php` | `Auth::requireRole("ADMIN")` | solo `ADMIN` |
| `actions/users.php` | `Auth::requireRole("ADMIN")` | solo `ADMIN` |

Semántica de `requireRole()` — la decisión final la toma el modelo vía `User::satisfies()`:

```php
public static function requireRole(string $required_role): void
{
    $user = self::user();                   // hidrata + cachea el User actual
    if (!$user) Api::redirect("/login.php"); // anónimo

    if ($required_role === "ANY") return;    // cualquier usuario logueado pasa

    if (!$user->satisfies($required_role)) {
        Api::redirect("/index.php");         // rol equivocado
    }
}
```

`Employee::satisfies($role)` compara el rol propio contra el exigido (heredado de `User`);
`Administrator::satisfies()` siempre devuelve `true` — es la forma polimórfica del "admin es
superconjunto".

### Filtrado a nivel de UI (aplicación blanda)

La barra lateral oculta las secciones que el rol no puede usar, y las páginas ocultan los botones
que el rol no puede tocar. Es azúcar de presentación encima de los guards duros de arriba.

**`Auth::canSee($page)`** — controla las entradas de la navbar; delega en el modelo:

```php
public static function canSee(string $page): bool
{
    //La matriz de permisos vive en el modelo (User::canSee).
    return self::user()?->canSee($page) ?? false;
}
```

La matriz vive en los modelos: `Employee::canSee()` responde `OVERVIEW`/`STOCK`/`SALES`/`USERS`
según el rol propio del empleado, y `Administrator::canSee()` devuelve `true` siempre. La corrección
que antes vivía en `Auth` (un empleado `STOCK` no ve la entrada "Ventas") ahora es parte de esa
matriz.

Layout del nav según rol:

| Entrada del nav | Vista | `ADMIN` | `STOCK` | `SALES` |
|-----------------|-------|:------:|:-------:|:-------:|
| Resumen (panel) | `index.php` | ✅ | ✅ | ✅ |
| Ventas | `views/sales.php` | ✅ | ❌ | ✅ |
| Inventario | `views/stock.php` | ✅ | ✅ | ✅ |
| Empleados | `views/users.php` | ✅ | ❌ | ❌ |

> **Nota:** `canSee()` es solo presentacional — decide qué *muestra* la barra lateral, no qué
> *permiten* las rutas. Hoy están alineadas (un `STOCK` no ve la entrada de Ventas), pero la matriz
> de rutas de arriba es la que manda de verdad.

**`Auth::canEdit($obj)`** — controla los botones de acción de una página; delega en el modelo:

```php
public static function canEdit(string $obj): bool
{
    //La matriz de permisos vive en el modelo (User::canEdit).
    return self::user()?->canEdit($obj) ?? false;
}
```

`Employee::canEdit($entity)` responde `true` solo si el rol propio coincide con la entidad (`STOCK` o
`SALES`); `Administrator::canEdit($entity)` siempre `true`.

Dónde se usa:

- `views/stock.php` — los botones "Agregar/Editar/Eliminar Vehiculo" se muestran solo si
  `canEdit("STOCK")`; el botón "vender este vehículo" solo si `canEdit("SALES")`.
- Comportamiento efectivo por rol en el listado de stock:

  | Acción | `ADMIN` | `STOCK` | `SALES` |
  |--------|:------:|:-------:|:-------:|
  | Ver inventario | ✅ | ✅ | ✅ |
  | Agregar / editar / eliminar vehículo | ✅ | ✅ | ❌ |
  | Crear venta desde una fila | ✅ | ❌ | ✅ |

---

## 5. Resumen de login / logout

- `login.php` — en GET renderiza el formulario; en POST llama a `Auth::login()`; éxito → `/index.php`,
  error → flash "Datos incorrectos" y de vuelta a `/login.php` (PRG).
- `logout.php` — `session_destroy()` y redirect a `/login.php`. A propósito no usa `autoload.php`
  porque el cierre de sesión no necesita nada más.
- `Auth::login()` — busca por email (`UserRepository::findByEmail`, SQL parametrizado) y verifica con
  `Crypto::passwordVerify()` (Argon2id). Solo los usuarios verificados reciben entradas de sesión.

---

## 6. Observaciones de seguridad

Lo bueno:

- Todas las queries son parametrizadas (prepared statements de PDO con
  `ATTR_EMULATE_PREPARES = false`).
- Las contraseñas se guardan solo como hash Argon2id; un campo de contraseña vacío al editar un
  usuario **no** pisa el hash existente (`UserRepository::update()` protege con `isset && !empty`).
- El `user_id` de las ventas sale de la sesión, nunca del formulario.
- El `update()` genérico whitelistea columnas por repositorio, bloqueando mass-assignment de
  `id`/`created`/`password_hash`.
- Las páginas de admin se protegen del lado del servidor (no solo ocultando la entrada del nav).

Para tener en cuenta / mejorar:

- La emulación de `METHOD` y los chequeos de rol dependen de la cookie de sesión; no hay token CSRF
  en los formularios de mutación.
- `logout.php` llama a `session_destroy()` sin limpiar `$_SESSION` primero (funciona, pero no
  invalida la cookie del lado del cliente).
- El listado de usuarios es solo-admin, pero la baja de un vehículo (stock) tiene un dialog de
  confirmación puramente client-side — la protección real es el guard `requireRole` de la acción,
  que es el que importa.