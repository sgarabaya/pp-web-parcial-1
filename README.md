# Ruta 9

Sistema web de gestión para una concesionaria de vehículos. Permite administrar
empleados, inventario de vehículos y ventas, con un panel de control que resume
métricas operativas. Es una aplicación PHP server-side rendering, sin framework,
sobre MySQL, distribuida con Docker.

## Descripción general

- **Dominio**: agencia / concesionaria de autos (0km). Tres áreas: administración,
  inventario (stock) y ventas.
- **Usuarios y roles**: `ADMIN`, `STOCK` y `SALES`, con control de acceso por
  página y por acción.
- **Funcionalidades**:
  - Login/logout por sesión con contraseñas hasheadas (Argon2id).
  - CRUD de usuarios (solo `ADMIN`): crear, editar, eliminar.
  - CRUD de vehículos (rol `STOCK`): marca, modelo, año, precio y unidades.
  - Registro de ventas (rol `SALES`): descontar stock automáticamente y en
    transacción.
  - Panel de control con métricas (total recaudado, unidades disponibles) y
    cuatro gráficos de barras (Chart.js): tendencia mensual de recaudación,
    modelos más vendidos, ventas mensuales por empleado y stock vs. ventas.
- **Interfaz**: HTML renderizado en servidor + CSS propio (tema oscuro), iconos
  Lucide y Chart.js vía CDN. Sin framework de frontend ni compilación.

## Estructura del proyecto

```
.
├── docker-compose.yml          # web (Apache+PHP) y db (MySQL)
├── Dockerfile                  # php:apache + pdo_mysql + mod_rewrite
├── migrations/
│   ├── 0001_InitialMigration.sql   # esquema + usuario admin seed
│   └── DemoSeed.sql                # datos de demostración
└── www/
    ├── index.php               # panel de control
    ├── login.php / logout.php
    ├── autoload.php            # autoloader + inicio de sesión
    ├── actions/                # endpoints POST (sale, stock, users)
    ├── views/                  # pantallas (create_sale, sales, stock, ...)
    ├── models/                 # clases de dominio (User, Vehicle, Sale, ...)
    ├── repos/                  # capa de persistencia (Repository, ...)
    ├── utilities/              # Auth, Api, Config, Crypto, Messages, Validator
    ├── components/             # header, navbar, footer
    ├── public/                 # style.css, charts.js, favicon
    └── tests/                  # pruebas manuales
```

Ver `docs/` para el detalle.

## Requisitos de software

- **PHP 8.3+** (usa named arguments, constructor property promotion y el
  atributo `#[\Override]`, introducido en PHP 8.3).
- **ext-pdo_mysql** habilitado.
- **Soporte de Argon2id** en `password_hash` (compilado por defecto en las
  imágenes oficiales `php:apache`).
- **MySQL 8.x** (o compatible, ej. MariaDB 10.6+) con `pdo_mysql`.
- **Apache** con `mod_rewrite` (ya habilitado en el `Dockerfile`; el `.htaccess`
  define las páginas de error). También funciona bajo cualquier server que sirva
  archivos PHP.
- **Docker + Docker Compose** (opcional, es la vía recomendada para correr el
  proyecto).
- Navegador moderno (usa `<dialog>`, trailing commas en JS y CSS moderno);
  conexión a Internet para los CDN (fuentes, Lucide, Chart.js).

## Puesta en marcha

```bash
docker compose up --build
```

- Web: http://localhost:8080
- MySQL: `localhost:3306` (usuario `ruta9` / contraseña `ruta9-pwd`, db `ruta9`)

Las migraciones de `migrations/` se ejecutan automáticamente la primera vez que
se inicializa el contenedor de la base (`/docker-entrypoint-initdb.d`), en orden
alfabético (`0001_InitialMigration.sql` y luego `DemoSeed.sql`). La base no tiene
volumen persistente propio: si se elimina el contenedor de `db`, se pierden los
datos.

### Credenciales por defecto

| Email | Password | Rol |
| ----- | -------- | --- |
| `admin@ruta9.ar` | `admin` | ADMIN (migración inicial) |
| `elva.bozzo@ruta9.ar`, `sofia.martinez@ruta9.ar` | `123` | ADMIN |
| `alejandro.lopez@ruta9.ar`, `valentina.gonzalez@ruta9.ar`, `diego.rodriguez@ruta9.ar` | `456` | STOCK |
| `carmen.perez@ruta9.ar`, `javier.sanchez@ruta9.ar`, `lucia.ramirez@ruta9.ar`, `carlos.cruz@ruta9.ar`, `isabella.torres@ruta9.ar`, `andres.flores@ruta9.ar`, `elena.gomez@ruta9.ar`, `miguel.diaz@ruta9.ar`, `camila.reyes@ruta9.ar`, `luis.morales@ruta9.ar` | `789` | SALES |

## Configuración por variables de entorno

| Variable  | Descripción           | Ejemplo (docker-compose) |
| --------- | --------------------- | ------------------------ |
| `DB_HOST` | Host de MySQL         | `db`                     |
| `DB_NAME` | Nombre de la base     | `ruta9`                  |
| `DB_USER` | Usuario de la base    | `ruta9`                  |
| `DB_PWD`  | Contraseña de la base | `ruta9-pwd`              |

Las lee `Config` desde el entorno real (`getenv`). No hay `.env` local: la
configuración se inyecta vía Docker Compose.

## Documentación

- [Arquitectura](docs/Arquitectura.md)
- [Autenticación](docs/Autenticacion.md)
- [Ciclo de vida de la API](docs/Ciclo%20de%20Vida.md)
- [Modelos de datos](docs/Modelos%20de%20Datos.md)
- [Patrones de diseño](docs/Patrones.md)