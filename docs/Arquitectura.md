# Arquitectura

Este documento describe la arquitectura de **Ruta 9** a alto nivel: la infraestructura en la que
corre (Docker, Docker Compose, MySQL), la estructura por capas del código PHP, cómo viaja una
request por el sistema y los patrones de diseño que se usan.

---

## 1. Panorama del sistema

```
                        ┌──────────────────────────────────────────────────┐
                        │                   Docker                         │
           HTTP         │  ┌────────────────────────────────────────────┐  │
Browser ──────────────► │  │  web  (Apache + PHP 8, puerto host 8080)  │  │
                        │  │  document root: /var/www/html             │  │
                        │  │  └─ montado desde ./www                   │  │
                        │  │     ├─ index.php      panel de control    │  │
                        │  │     ├─ views/         render HTML         │  │
                        │  │     ├─ actions/       controladores POST  │  │
                        │  │     ├─ models/        objetos de dominio  │  │
                        │  │     ├─ repos/         persistencia PDO    │  │
                        │  │     ├─ utilities/     servicios estáticos │  │
                        │  │     └─ components/    parciales de layout │  │
                        │  └────────────────────────┬──────────────────┘  │
                        │                           │ PDO (pdo_mysql)     │
                        │  ┌────────────────────────▼──────────────────┐  │
                        │  │  db  (MySQL, puerto host 3306)            │  │
                        │  │  base de datos: ruta9                     │  │
                        │  │  ./migrations → /docker-entrypoint-       │  │
                        │  │                    initdb.d (primer boot)  │  │
                        │  └───────────────────────────────────────────┘  │
                        └──────────────────────────────────────────────────┘
```

Dos contenedores se hablan por una red interna de Docker (el `web` resuelve el host de la base como
`db`). Lo único que ve el navegador es el contenedor de Apache/PHP en el puerto `8080`.

---

## 2. Infraestructura

### 2.1 Dockerfile (`Dockerfile`)

```dockerfile
FROM php:apache
RUN apt-get update
RUN docker-php-ext-install pdo_mysql
RUN apt-get clean
RUN rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite
```

- Se basa en la imagen oficial **`php:apache`**: te da Apache con mod_php de fábrica.
- Instala la extensión **`pdo_mysql`**, la única que necesita la app (toda la persistencia se apoya
  en PDO).
- Habilita **`mod_rewrite`** (para URLs limpias tipo `/views/stock.php`).
- El repo no agrega configuración custom de PHP; aplica el `php.ini` default de la imagen.

### 2.2 Docker Compose (`docker-compose.yml`)

Dos servicios:

| Servicio | Imagen / build          | Puertos      | Volúmenes                              | Variables de entorno                           |
|----------|-------------------------|--------------|----------------------------------------|------------------------------------------------|
| `web`    | `build: .` (php:apache) | `8080:80`    | `./www:/var/www/html`                  | `DB_HOST=db`, `DB_NAME=ruta9`, `DB_USER=ruta9`, `DB_PWD=ruta9-pwd` |
| `db`     | `mysql:latest`          | `3306:3306`  | `./migrations:/docker-entrypoint-initdb.d` | `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE=ruta9` |

Puntos clave:

- **Montaje del código en vivo**: `./www` se monta directo en el document root de Apache, así que
  los cambios en los PHP se ven al toque, sin rebuild de la imagen.
- **Inicialización del esquema al primer boot**: la imagen oficial de MySQL ejecuta cada `*.sql` de
  `/docker-entrypoint-initdb.d` una sola vez, la primera vez que se crea el volumen de datos. Como
  ahí está montada la carpeta de migraciones, primero corre `0001_InitialMigration.sql` (crea el
  esquema + el usuario admin) y después `DemoSeed.sql` (poblá los datos de demo).
- **Configuración por variables de entorno**: la app PHP nunca hardcodea credenciales de la base.
  Lee las variables `DB_*` en runtime a través de `Config` (más abajo), lo que desacopla los
  servicios `web` y `db` y los hace portables.
- El puerto de MySQL `3306` también queda expuesto en el host, lo que es cómodo para inspeccionar
  los datos con un cliente local (`mysql -h 127.0.0.1 -u ruta9 -pruta9-pwd ruta9`). El costo es
  dejar un puerto de base expuesto (ok para un entorno local/de TP).

---

## 3. Configuración en runtime

`www/utilities/Config.php` es una clase abstracta estática que envuelve `getenv()`:

```php
abstract class Config
{
    public static function getDbHost(): string { /* DB_HOST */ }
    public static function getDbName(): string { /* DB_NAME */ }
    public static function getDbUser(): string { /* DB_USER */ }
    public static function getDbPwd(): string  { /* DB_PWD */ }
}
```

Estos cuatro accesors son el **único** lugar del código que lee variables de entorno.
`Database::connect()` los usa para armar el DSN y las credenciales — o sea, no hay credenciales
hardcodeadas en ningún lado de los fuentes PHP (en un commit pasado se sacaron unas que había).

---

## 4. Estructura del código por capas

El directorio `www/` está organizado por responsabilidad, con un autoloader liviano que reemplaza a
las cadenas de `require_once`.

```
www/
├── autoload.php              # Autoloader estilo PSR-0 + arranque de sesión
├── index.php                 # Panel de control (portada)
├── login.php / logout.php    # Entrada/salida de sesión
├── models/                   # Objetos de dominio pelados (User y subclases, Vehicle, Sale, SaleView, MetricasDashboard)
├── repos/                    # Capa de persistencia (Database, Repository base + repos concretos)
├── actions/                  # Controladores solo-POST (stock, users, sale)
├── views/                    # Páginas HTML (stock, users, sales, create_sale, edit_stock, edit_user)
├── components/               # Parciales de layout (header, navbar, footer)
├── utilities/                # Clases helper estáticas (Config, Api, Auth, Crypto, Validator, Messages)
├── public/                   # Assets estáticos (style.css, charts.js, favicon.png)
└── tests/                    # Scripts de test exploratorios (no integrados a ningún runner)
```

| Capa        | Responsabilidad                                                          | Ejemplos                          |
|-------------|--------------------------------------------------------------------------|-----------------------------------|
| **Utilities** | Servicios transversales, sin estado entre requests (salvo flash y caché de auth en sesión) | `Auth`, `Api`, `Crypto`, `Config`, `Validator`, `Messages` |
| **Models**    | Objetos de dominio puros con `mapFrom()`/`mapTo()` para serializar fila ↔ objeto | `User` (abstracta) + `Employee`/`Administrator`, `Vehicle`, `Sale`, `SaleView` |
| **Repos**     | Todo el SQL; la clase base `Repository` implementa el CRUD genérico     | `Repository`, `UserRepository`, `VehicleRepository`, `SaleRepository` |
| **Actions**   | Controladores: validan entrada, orquestan repos, setean mensaje flash y redirigen (PRG) | `actions/stock.php`, `actions/users.php`, `actions/sale.php` |
| **Views**     | Renderizan tablas/formularios HTML, aplican los guards de autorización, y solo leen con los repos | `views/stock.php`, `views/sales.php`, … |
| **Components**| Esqueleto de la página, barra lateral, toast de mensaje flash             | `header.php`, `navbar.php`, `footer.php` |

### Reglas de capas que se ven en el código

- **Las vistas nunca escriben**; solo llaman a los repos para lecturas (`findAll()`,
  `fetchDetails()`).
- **Las acciones nunca renderizan**; validan, mutan vía repos, setean el flash y redirigen (todo el
  patrón **Post/Redirect/Get**).
- **Los repos son la única capa que toca PDO/MySQL.** Los modelos son objetos de dominio que no
  saben consultar; solo saben mapearse de/para arrays asociativos.
- **Los concernimientos transversales** (sesión, chequeos de auth, cripto, mensajes al usuario)
  viven en las clases estáticas de utilities y los usan todas las capas por arriba de datos.

---

## 5. Flujo de una request (a alto nivel)

```
 Browser                   Apache/PHP                App
   │   GET /views/stock.php    │                        │
   ├──────────────────────────►│  autoload.php          │
   │                           │   │ spl_autoload_register
   │                           │   │ Auth::load() (sesión)
   │                           │   ▼
   │                           │  views/stock.php
   │                           │   │ Auth::ensureLoggedIn()
   │                           │   │ VehicleRepository::findAll()
   │                           │   ▼
   │                           │  render HTML (header/nav/footer)
   │   ◄───────────────────────┤
   │   POST /actions/stock.php │
   ├──────────────────────────►│  autoload.php
   │                           │   │ Auth::requireRole("STOCK")
   │                           │   │ Validator → repository
   │                           │   │ Api::set_message() → redirect
   │   ◄── 302 Location: /views/stock.php
   │   GET /views/stock.php (el toast flash aparece en el footer)
```

Todo punto de entrada (página o acción) arranca incluyendo `autoload.php`; ese es el único punto de
boot. El recorrido línea por línea está en
[Ciclo de Vida.md](Ciclo%20de%20Vida.md).

---

## 6. Patrones usados (de una mirada)

| Patrón | Dónde | Qué hace acá |
|--------|-------|--------------|
| **Repository** | `repos/Repository.php` + repos concretos | Aísla todo el SQL/PDO del resto de la app; CRUD genérico heredado por todos los repos de dominio. |
| **Template Method (método plantilla)** | clase base `Repository` | `findById`/`findAll`/`create`/`update`/`delete` definen el algoritmo; las subclases solo aportan nombre de tabla, whitelist de columnas y los mapeos fila↔objeto. |
| **Mapeo de modelos (variante Data Mapper)** | `mapFrom()` / `mapTo()` en cada modelo | El repo le pasa filas snake_case crudas al modelo, que arma un objeto tipado; el objeto se serializa de vuelta a fila para inserts/updates. |
| **Singleton** | `Database::connect()` | Una sola conexión PDO por request, creada de forma perezosa al primer uso. |
| **Interfaz fluida (fluent interface)** | `Validator` | `field("x")->is_required()->is_email()->has_max_length(40)` se lee solo. |
| **Clases de servicios estáticas (fachada)** | `Auth`, `Api`, `Crypto`, `Config`, `Messages` | Helpers sin estado (config, sesión/flash, hash de contraseñas, strings al usuario). |
| **Value object / read model** | `SaleView` | Snapshot de solo lectura de la query de ventas con joins; nunca se escribe a la base. |
| **Post/Redirect/Get (PRG)** | `actions/*.php` | Todo POST que muta termina en `Api::redirect()`, evitando el reenvío del formulario; el feedback viaja en un mensaje flash de sesión que renderiza `footer.php`. |
| **Emulación de verbos HTTP** | campo oculto `METHOD` | Los formularios HTML solo soportan GET/POST; las acciones despachan según un campo oculto `METHOD=POST/PUT/DELETE`. |

Cada patrón en detalle, con código, está en [Patrones.md](Patrones.md).

---

## 7. Cómo mapea la consigna a lo que hay en el código

El `TP.md` pedía ciertas features de POO; así se implementa cada una:

| Requisito | Implementación |
|-----------|----------------|
| Programación orientada a objetos | Clases repartidas entre `models/`, `repos/`, `utilities/`, más las acciones/vistas procedimentales arriba. |
| Constructores, visibilidad, getters/setters | Promoción de propiedades en el constructor; `User` expone getters/setters (estado `private`/`protected`), `Vehicle`/`Sale` usan propiedades promovidas `public`. |
| Herencia | `Repository` (abstracta) → `UserRepository`, `VehicleRepository`, `SaleRepository`. |
| Interfaces o clase abstracta | `Repository` es la clase abstracta; también `Config`, `Api`, `Auth`, `Crypto`, `Messages` son clases abstractas estáticas. |
| Métodos y propiedades estáticos | `Crypto::uuid4/passwordHash`, `Auth::*`, `Api::*`, `Config::*`, y `MetricasDashboard` (métodos estáticos de agregación + contador de visualizaciones por sesión). |
| Conexión a MySQL | `Database` (singleton PDO) + `pdo_mysql` en la imagen. |
| Formularios HTML + POST | Todas las mutaciones son formularios HTML que postean a `actions/*`. |
| Usuario → Empleado/Administrador (herencia) | `User` es una clase **abstracta** con fábricas `mapFrom()`/`create()` que despachan por rol: `ADMIN` → `Administrator`, `STOCK`/`SALES` → `Employee`. Las decisiones de permiso son polimórficas (`canSee()`/`canEdit()`/`satisfies()`): `Employee` codifica la matriz del empleado, `Administrator` responde `true` siempre. La tabla `Users` y su `ENUM('ADMIN','STOCK','SALES')` no cambian. Ver [Patrones.md](Patrones.md#3-mapeo-de-modelos-mapfrom--mapto). |

---

## 8. Observaciones / bordes conocidos

Cosas que conviene saber si vas a tocar el proyecto:

- **El contador de visualizaciones vive en la sesión.** `MetricasDashboard` ya no usa una propiedad
  estática: `registrarVisualizacion()` incrementa `$_SESSION["visualizaciones"]` y
  `get_visualizaciones_sesion()` lo lee. Así el conteo sí persiste entre requests de la misma
  sesión (ver [Ciclo de Vida.md](Ciclo%20de%20Vida.md#6-el-panel-de-control--indexphp)).
- **`www/tests/test_ventas.php` es exploratorio.** Instancia `Sale`/`SaleRepository` de formas que
  ya no matchean los constructores actuales (el código evolucionó de más) y no está conectado a
  ningún runner de tests.
- **No hay runner de migraciones**: los cambios de esquema se aplican editando los SQL y
  recreando el volumen de MySQL (o ejecutando el SQL a mano). El servicio `db` solo re-ejecuta los
  scripts de `initdb.d` con un volumen de datos nuevo.
- **No hay `.htaccess`**; `mod_rewrite` está habilitado pero la app usa los directory indexes y
  paths estáticos default.