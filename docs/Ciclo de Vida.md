# Ciclo de vida de la API

La "API" del sistema son los **entrypoints PHP**: páginas GET (`views/`)
que leen y renderizan, y endpoints POST (`actions/`) que mutan estado. No hay
API REST JSON; las mutaciones se hacen con formularios HTML y redirecciones
(patrón PRG).

## Métodos HTTP simulados

Los formularios HTML solo permiten `GET` y `POST`. Para representar PUT y
DELETE, `actions/` lee un campo oculto `METHOD`:

```
<input type="hidden" name="METHOD" value="PUT" />
```

`users.php` y `stock.php` despachan por `switch ($method)`:

| `METHOD` | Función     | Uso |
| -------- | ----------- | --- |
| `POST`   | `create()`  | alta |
| `PUT`    | `update()`  | edición (requiere `id`) |
| `DELETE` | `delete()`  | baja (requiere `id`) |

`sale.php` solo acepta `POST` (registro de venta).

## Ciclo de vida de una mutación (actions/*)

```
1. require autoload.php            → autoloader + Auth::load() (sesión)
2. Auth::requireRole(...)          → guardia por rol (redirect si falla)
3. ¿REQUEST_METHOD === "POST"?     → si no, excepción genérica
4. Api::get_request()              → copia de $_POST
   ¿vacío?                         → "Payload invalido..."
5. Validación fluida (Validator)   → si inválida, error con detalle por campo
6. Construcción del modelo:
     new/Sale/Vehicle(...) con Crypto::uuid4() y DateTimeImmutable("now")
7. Persistencia vía repositorio:
     repository->create(...) | update(...) | delete(...)
   ⚠ sale: SaleRepository::registerSale() usa transacción (ver abajo)
8. Mensaje de resultado a la sesión:
     Api::set_message(operación exitosa, "success")
     Api::set_error_message($e->getMessage(), "error")  (catch)
9. redirect (finally): /views/sales.php | /views/stock.php | /views/users.php
```

Los `catch` envuelven cualquier `Exception` en un mensaje flash; los
`finally` redirigen siempre, por lo que nunca se renderiza la vista desde una
action.

### Registro de venta (`sale.php` → `SaleRepository::registerSale`)

Pasos con reglas de dominio:

1. Validación de la venta (vehículo, monto, cliente, contacto, método).
2. El usuario autenticado se toma de `Auth::user()` (la venta queda a su nombre).
3. `registerSale()`:
   - Verifica que exista el `user_id` y el `vehicle_id`.
   - Verifica `stock > 0`; si no, error.
   - `beginTransaction()` → `create(Sale)` → `stock -= 1` en el vehículo →
     `commit()`.
   - En excepción: `rollBack()` y re-lanza.

La consistencia (venta + decremento de stock) depende de que ambas escrituras
vivan en la misma transacción.

## Ciclo de vida de una lectura (views/*)

```
1. require autoload.php (sesión)
2. Guardia: Auth::requireRole(...) o Auth::user() → redirect a login si no
3. Carga: repositorios (findAll, findById, fetchDetails, ...)
4. Render: header.php → navbar.php(sección) → contenido → footer.php
```

- `navbar()` filtra las entradas del menú con `User::canSee()`.
- `footer.php` consume el mensaje flash de la sesión (toast) y lo limpia.
- `index.php` además registra una visualización por sesión
  (`MetricasDashboard::registrarVisualizacion()`).

## Mensajes al usuario (flash + PRG)

Cada action termina con un redirect; el resultado viaja en la sesión:

```
Api::set_message(texto, tipo)      # tipo: "success" | "error"
Api::set_error_message(texto)      # tipo = "error"
```

`footer.php` renderiza el toast (con barra de progreso, se auto-destruye a los
3 s) y resetea `$_SESSION["message"]` y `$_SESSION["message_type"]` para que el
mensaje se muestre una sola vez.

## Ciclo de vida de la sesión y del usuario

```
login.php (GET)  → formulario
login.php (POST) → Auth::login() → ok: $_SESSION["user.id"]
                                    fail: mensaje "Datos incorrectos"
index.php        → Auth::user() (lazy load desde DB, cacheado en estático)
logout.php       → session_destroy() → /login.php
```

- `Auth::load()` arranca la sesión en **toda** entrada que incluya
  `autoload.php` (views, actions, index, login).
- El usuario no se guarda serializado en la sesión: solo su `id`, para que
  cambios de rol/estado se reflejen al próximo request.

## Diagrama de secuencia (POST create, ejemplo stock)

```
Cliente            views/edit_stock.php      actions/stock.php      VehicleRepository   MySQL
   │ formalario POST ──►    (form)                │                      │                │
   │                       load si ?id=          │                      │                │
   │  POST /actions/stock.php ──────────────────►│  requireRole(STOCK)  │                │
   │                                             │  validar → Vehicle    │                │
   │                                             │  create(vehicle) ────►│  INSERT ──────►│
   │                                             │  set_message(...)     │                │
   │  ◄── 302 /views/stock.php ──────────────────┤                      │                │
   │  GET /views/stock.php ──────────────────────────────────────────────┴──► findAll()  │
   └── render + toast                                                              │
```