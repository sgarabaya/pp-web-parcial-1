# Arquitectura

## Resumen

Aplicación web clásica server-side en PHP sin framework, siguiendo un esquema
cercano a **MVC simplificado**:

- **Modelo**: `www/models/` (clases de dominio) + `www/repos/` (persistencia).
- **Vista**: `www/views/` y `www/components/` (HTML + PHP embebido).
- **Controlador**: `www/actions/` (endpoints de formularios) + los propios
  entrypoints (`index.php`, `login.php`, etc.).

No hay front controller: cada URL es un archivo PHP, y la lógica común se
encadena a través de `autoload.php`, que todas las páginas incluyen primero.

## Flujo de una petición

```
Browser
  │  GET /views/sales.php  (o POST /actions/sale.php)
  ▼
Apache + mod_rewrite  (ErrorDocument 404/500 → /index.php)
  ▼
entrypoint PHP (views/*.php | actions/*.php | index.php | login.php)
  │
  ├─ require autoload.php
  │    ├─ spl_autoload_register: "clase" → {models,repos,components,utilities}/Clase.php
  │    └─ Auth::load(): session_start() + leer $_SESSION["user.id"]
  │
  ├─ control de acceso (Auth::requireRole(...) o Auth::user())
  ├─ (actions) validación → dominio → persistencia → mensaje flash
  ├─ (views) carga de datos vía repositorios
  └─ render: components/header.php + navbar.php + vista + footer.php
```

## Capas

| Capa            | Ubicación                | Responsabilidad |
| --------------- | ------------------------ | --------------- |
| Entrypoints     | `index.php`, `login.php`, `logout.php`, `views/*`, `actions/*` | Recibir la petición, orquestar y responder |
| Vista           | `views/`, `components/`  | Render del HTML, menú según `canSee`, datos ya cargados |
| Controlador     | `actions/`               | Validar entrada, construir modelos, llamar repos, redirigir |
| Dominio         | `models/`                | Entidades (User, Vehicle, Sale, SaleView) y reglas de rol |
| Persistencia    | `repos/`                 | Acceso a datos; `Repository` genérico + repos específicos |
| Soporte         | `utilities/`             | `Auth`, `Api`, `Config`, `Crypto`, `Messages`, `Validator` |

### Casos particulares

- **`MetricasDashboard`** (models) no es una entidad: es un helper estático que
  recibe un `PDO` y ejecuta consultas agregadas (`SUM`, `COUNT`). Recibe la
  conexión por parámetro en lugar de usar `Database::connect()`.
- **`SaleView`** (models) es un modelo de lectura (read model) sin `mapTo()`,
  usado para listar ventas ya resueltas (nombre de usuario y vehículo) sin
  exponer IDs.

## Frontend

- HTML renderizado en servidor; sin SPA ni build step.
- `public/style.css`: tema propio, oscuro, variables CSS en `:root`, layout con
  sidebar fija.
- `public/charts.js`: arma cuatro gráficos de barras (Chart.js 4.5.1) en el
  panel a partir del listado plano de ventas (`fetchSalesOverview`): tendencia
  mensual de recaudación, vehículos más vendidos, ventas mensuales por empleado
  (apiladas) y unidades vendidas vs. inventario actual por modelo (doble eje).
  La agrupación de datos se hace en el cliente (`groupBy`).
- Iconos: Lucide (`unpkg`). Fuentes: Google Fonts. Ambos por CDN en
  `components/header.php` / `components/footer.php`.
- Mensajes al usuario: toasts consumidos desde la sesión (ver
  [Ciclo de Vida](Ciclo%20de%20Vida.md#mensajes-al-usuario)).

## Infraestructura

- `Dockerfile`: imagen `php:apache`, instala `pdo_mysql`, habilita `mod_rewrite`.
- `docker-compose.yml`:
  - Servicio `web`: mountea `./www` en `/var/www/html`, expone `:8080`.
  - Servicio `db`: `mysql:latest`, expone `:3306`, inicializa con el contenido
    de `migrations/` (montado en `/docker-entrypoint-initdb.d`).
  - Conexión entre ambos por variables de entorno (`DB_*`).
- Sin volumen persistente para los datos de MySQL: los datos viven en la capa
  del contenedor y se pierden si se recrea `db`.

## Convenciones

- **Nombres de clase = nombre de archivo**: el autoloader busca
  `{directorio}/Clase.php`.
- **IDs**: UUID v4 generados en la aplicación (`Crypto::uuid4()`), almacenados
  como `VARCHAR(36)`.
- **Fechas**: `DateTimeImmutable` en dominio, formato `ATOM` al persistir,
  columna `DATETIME` en MySQL.
- **Tipado**: `declare(strict_types=1)` en `models/` y `repos/`; tallas de
  campos validados contra el esquema (40 chars, etc.).
- **Idioma del código**: mezcla inglés (nombres) y español (mensajes de
  usuario); los mensajes de dominio viven en `Messages`.

## Árbol de archivos

```
www/
├── index.php
├── login.php
├── logout.php
├── autoload.php
├── actions/    sale.php · stock.php · users.php
├── views/      create_sale.php · edit_stock.php · edit_user.php
│               sales.php · stock.php · users.php
├── models/     Administrator.php · Employee.php · MetricasDashboard.php
│               Sale.php · SaleView.php · User.php · Vehicle.php
├── repos/      Database.php · Repository.php · SaleRepository.php
│               UserRepository.php · VehicleRepository.php
├── utilities/  Api.php · Auth.php · Config.php · Crypto.php
│               Messages.php · Validator.php
├── components/ footer.php · header.php · navbar.php
├── public/     charts.js · favicon.png · style.css
└── tests/      test_ventas.php
```