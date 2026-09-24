# Autenticación y autorización

## Enfoque

Autenticación por **sesiones nativas de PHP** (cookie de sesión), sin tokens
JWT ni OAuth. La sesión guarda únicamente el `id` del usuario; el objeto
`User` se reconstruye desde la base por request.

```
login.php (POST email + password)
  └─ Auth::login()
       ├─ UserRepository::findByEmail()       → User | null
       ├─ Crypto::passwordVerify(pass, hash)  → Argon2id
       └─ $_SESSION["user.id"] = user->getId()
```

- `Auth::load()`: llamado desde `autoload.php` en cada entrada; arranca la
  sesión y lee `user.id` del `$_SESSION`.
- `Auth::user()`: carga perezosa del usuario por `UserRepository::findById()`
  y lo cachea en el atributo estático `Auth::$user` (un solo request → una sola
  consulta).
- `logout.php`: `session_destroy()` + redirect a `/login.php`.

## Contraseñas

- Hash y verificación con **Argon2id** (`PASSWORD_ARGON2ID`), parámetros por
  defecto de PHP (`v=19, m=65536, t=4, p=1` según los hashes seed).
- Al crear/editar un usuario, el hash se calcula en la aplicación
  (`Crypto::passwordHash()`); `UserRepository::update()` hashea el campo
  `password` antes de delegar en el update genérico.

## Guardias

- `Auth::requireRole(string $role)`: usado por actions y vistas restringidas.
  - Sin sesión → redirect a `/login.php`.
  - `"ANY"` → pasa (index).
  - `!user->satisfies(role)` → redirect a `/index.php` (no hay 403; el fallo
    redirige en silencio).
- `User::satisfies()` está polimorfizado:
  - `Employee`: `role === $requiredRole` (igualdad estricta).
  - `Administrator`: siempre `true` (**el admin pasa toda guardia**).
- Autorización de interfaz, delegada también al modelo:
  - `User::canSee(page)` decide qué entradas muestra el navbar.
  - `User::canEdit(entity)` decide qué botones de acción se renderizan
    (stock: “Agregar/Editar/Eliminar” y “Registrar venta”).

## Matriz de roles

Lectura (GET):

| Página                    | ADMIN | STOCK | SALES |
| ------------------------- | :---: | :---: | :---: |
| `index.php` (Panel)       | ✅    | ✅    | ✅    |
| `views/stock.php`         | ✅    | ✅    | ✅    |
| `views/create_sale.php`   | ✅    | ✅    | ✅    |
| `views/sales.php`         | ✅    | ✅*   | ✅    |
| `views/users.php`         | ✅    | ❌    | ❌    |
| `views/edit_user.php`     | ✅    | ❌    | ❌    |
| `views/edit_stock.php`    | ✅    | ✅*   | ❌    |

\* `views/sales.php` exige `requireRole("SALES")`; como `Administrator::satisfies()`
siempre devuelve `true`, el admin entra. Idem `views/edit_stock.php` con
`requireRole("STOCK")`. `stock.php` y `create_sale.php` solo piden usuario
autenticado (la restricción real está al enviar el formulario).

Escritura (POST, actions):

| Action             | ADMIN | STOCK | SALES | Operaciones |
| ------------------ | :---: | :---: | :---: | ----------- |
| `actions/users.php`| ✅    | ❌    | ❌    | crear / editar / eliminar usuarios |
| `actions/stock.php`| ✅    | ✅    | ❌    | crear / editar / eliminar vehículos |
| `actions/sale.php` | ✅    | ❌    | ✅    | registrar venta |

UI condicionada en `views/stock.php`: la columna de acciones muestra botones de
edición/eliminación solo si `canEdit("STOCK")`, y el botón “Registrar venta”
solo si `canEdit("SALES")`. Con `Employee`, `canEdit(entity)` es
`role === entity`; con `Administrator` siempre `true` (ve todos los botones).

Visibilidad del menú (navbar) vía `canSee`:

| Entrada   | ADMIN | STOCK | SALES |
| --------- | :---: | :---: | :---: |
| Resumen   | ✅    | ✅    | ✅    |
| Ventas    | ✅    | ❌    | ✅    |
| Inventario| ✅    | ✅    | ✅    |
| Empleados | ✅    | ❌    | ❌    |

## Usuarios (demo)

| Email                 | Rol   | Password |
| --------------------- | ----- | -------- |
| `admin@ruta9.ar`      | ADMIN | `admin`  |
| `jorge.perez@ruta9.ar`| STOCK | `jorge`  |
| `florencia.flores@ruta9.ar` | SALES | `flor` |
| `enzo.garcia@ruta9.ar`| SALES | `enzo`   |
| `elva.bozzo@ruta9.ar` | SALES | `elva`   |

## Consideraciones de seguridad (estado actual)

- Los formularios **no usan tokens CSRF**; la sesión se destruye por GET en
  `logout.php` (vulnerable a logout CSRF).
- El login **no tiene rate limiting** ni bloqueo por intentos; ante datos
  inválidos devuelve el mensaje genérico “Datos incorrectos”.
- Las credenciales de la base (`ruta9`/`ruta9-pwd`) viajan en texto plano en el
  `docker-compose.yml` y por variables de entorno, aptas solo para desarrollo.
- Todos los querys usan **prepared statements** (`PDO::prepare`), sin
  concatenación de entrada del usuario. La única interpolación es de nombres de
  tabla/columna provistos por el propio código (no por el usuario), vía
  `sprintf` en `Repository`.