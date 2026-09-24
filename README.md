# Ruta 9 — Documentación

**Ruta 9** es un sistema de gestión web para una agencia de autos, hecho con **PHP orientado a
objetos**. Te deja que un equipo de empleados (con distintos roles) entre al sistema, gestione el
inventario de vehículos, registre ventas y — si sos administrador — administre los usuarios. La app
corre en **Docker** (Apache + PHP) contra una base **MySQL** y sigue una arquitectura liviana tipo
MVC, hecha a mano, sin frameworks.

Es un trabajo práctico universitario (`TP.md` en la raíz del repo tiene la consigna original). Esta
documentación explica cómo funciona el código de verdad.

## Arranque rápido

```bash
# Desde la raíz del repo
docker compose up --build
```

Y listo, abrí <http://localhost:8080> y entrá con alguna de las cuentas precargadas:

| Rol    | Email                    | Contraseña |
|--------|--------------------------|------------|
| Admin  | `admin@ruta9.ar`         | `admin`    |
| Stock  | `jorge.perez@ruta9.ar`   | `jorge`    |
| Ventas | `florencia.flores@ruta9.ar` | `flor`   |

En el primer arranque, el contenedor de MySQL ejecuta solo el primer script de migración/seed que
está montado desde `./migrations` (los detalles en [Modelos de Datos.md](Modelos%20de%20Datos.md#migraciones-y-datos-de-demo)).

## Índice de la documentación

| Archivo | Qué cubre |
|---------|-----------|
| [Arquitectura.md](Arquitectura.md) | La arquitectura a alto nivel: Docker/Docker Compose/MySQL, la estructura del código por capas, el flujo de una request y los patrones de una mirada. |
| [Patrones.md](Patrones.md) | En detalle los patrones de diseño: Repository, método plantilla, mapeo de modelos, clases de servicios estáticas, validación fluida, PRG, etc. |
| [Ciclo de Vida.md](Ciclo%20de%20Vida.md) | El funcionamiento interno: secuencia de arranque, autenticación y recorridos paso a paso del login, los ABM y el registro de ventas. |
| [Modelos de Datos.md](Modelos%20de%20Datos.md) | El esquema de MySQL, las migraciones, los datos de demo y las credenciales. |
| [Autenticacion.md](Autenticacion.md) | Cómo funcionan la autenticación y el control de acceso por roles, con la matriz completa de permisos. |

## Resumen de funcionalidades

- **Login / logout** con email + contraseña (las contraseñas se guardan hasheadas con Argon2id).
- **Acceso por roles**: `ADMIN`, `STOCK` y `SALES`; el admin puede hacer todo.
- **Gestión de vehículos (stock)**: alta, listado, modificación y baja.
- **Ventas**: registrar una venta contra un vehículo (precio, cliente, medio de pago); el stock se
  descuenta de forma atómica dentro de una transacción de la base.
- **Gestión de usuarios** (solo admin): alta, listado, modificación y baja de empleados.
- **Panel de control** (`index.php`): muestra el stock disponible, el total histórico recaudado
  (admin), un contador de visualizaciones del panel por sesión (persiste en `$_SESSION`) y un
  gráfico de torta (Chart.js) con los ingresos y la cantidad de ventas por empleado.
- **Toasts de mensajes** (flash) y una barra lateral filtrada por rol.

## Stack tecnológico

- **PHP 8.3+** (usa promoción de propiedades en el constructor, argumentos nombrados y el atributo
  `#[\Override]`)
- **Apache** con `mod_rewrite` (imagen oficial `php:apache` de Docker)
- **MySQL** (latest) con el driver PDO de MySQL
- **Docker Compose** para orquestar todo localmente
- **HTML/CSS/JS** pelado del lado del cliente (íconos de Lucide, sin framework)