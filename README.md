# Sistema Escolar · CRUD + API (PHP vanilla)

CRUD completo sobre la base de datos `escuela` (MariaDB/MySQL) usando **PHP 8 sin frameworks ni dependencias**. Incluye vistas HTML renderizadas en servidor y una **API JSON** con las mismas reglas de validación.

## Funcionalidades

- CRUD de **alumnos, profesores, grupos, materias e inscripciones**.
- **Inicio de sesión de administradores** con contraseña hasheada y **tokens por dispositivo**.
- Búsqueda, filtros, ordenamiento y paginación en todos los listados.
- Validación en servidor: campos obligatorios, longitudes, fechas, correos, rangos, llaves foráneas y valores únicos (incluidas llaves compuestas).
- Consulta de **boletas** usando la vista `vista_boleta`, con filtros por alumno/ciclo y resumen de promedio.
- Protección **CSRF** en formularios HTML.
- Consultas con **PDO y sentencias preparadas** (sin SQL concatenado con datos del usuario).
- API JSON con códigos HTTP y errores de validación por campo.
- Interfaz adaptable (escritorio y móvil) y estilos de impresión para boletas.

## Requisitos

- PHP 8.0 o superior con extensiones `pdo_mysql` y `mbstring`.
- MariaDB / MySQL.
- La base de datos `escuela` puede crearse con las migraciones incluidas (recomendado) o importando `escuela.sql`.

## Instalación

### Opción A · XAMPP con Apache (recomendada)

1. Copia la carpeta del proyecto dentro de `C:\xampp\htdocs\escuela-crud`.
2. Inicia **Apache** y **MySQL** desde el panel de XAMPP.
3. Crea la base de datos y las tablas con las migraciones:

   ```bash
   C:\xampp\php\php.exe bin\migrate.php migrate --seed
   ```

   > `--seed` carga los datos de ejemplo. Si no los quieres, ejecuta solo `migrate`.
   > También puedes importar el esquema clásico con `C:\xampp\mysql\bin\mysql.exe -u root < escuela.sql`.

4. Crea tu cuenta de administrador (no hay credenciales por defecto):

   ```bash
   C:\xampp\php\php.exe bin\admin.php create admin --nombre="Administrador del sistema" --correo=admin@escuela.mx
   ```

   > El comando genera una contraseña aleatoria y la muestra **una sola vez**. Guárdala y cámbiala al iniciar sesión.
   > Si prefieres definirla tú, agrega `--password="TuClaveSegura2026!"` (mínimo 8 caracteres).

5. Ajusta las credenciales de base de datos si es necesario en `app/config.php` (por defecto `root` sin contraseña).
6. Abre `http://localhost/escuela-crud/login` e inicia sesión.

> El proyecto incluye `.htaccess`, así que las URLs limpias funcionan si Apache tiene `mod_rewrite` y `AllowOverride All` habilitados.

### Opción B · Servidor embebido de PHP

```bash
C:\xampp\php\php.exe -S localhost:8000 router.php
```

Luego abre `http://localhost:8000/login` e inicia sesión con la cuenta creada.

### Si las URLs limpias dan 404

Edita `app/config.php` y cambia:

```php
'pretty_urls' => false,
```

Con eso el sistema usará URLs del tipo `index.php?r=alumnos`. Todo seguirá funcionando.

## Migraciones de base de datos

El proyecto incluye un sistema propio de migraciones SQL, sin dependencias externas.

- `database/migrations/` → migraciones versionadas (`AAAA_MM_DD_NNNNNN_descripcion.sql`).
- `database/migrations/*.down.sql` → reverso de cada migración.
- `database/seeders/` → datos de ejemplo idempotentes.
- `bin/migrate.php` → ejecutor de consola.

Las migraciones aplicadas se registran en la tabla `migraciones` (nombre, lote y fecha). Al ejecutar el primer comando se crean automáticamente la base de datos y la tabla de control si no existen.

### Comandos

```bash
# Aplicar las migraciones pendientes
C:\xampp\php\php.exe bin\migrate.php migrate

# Aplicar migraciones y cargar datos de ejemplo
C:\xampp\php\php.exe bin\migrate.php migrate --seed

# Ver el estado de cada migración
C:\xampp\php\php.exe bin\migrate.php status

# Revertir el último lote (usa los archivos .down.sql)
C:\xampp\php\php.exe bin\migrate.php rollback

# Revertir un lote concreto
C:\xampp\php\php.exe bin\migrate.php rollback --batch=1

# Cargar solo los datos de ejemplo (se puede repetir sin duplicar)
C:\xampp\php\php.exe bin\migrate.php seed

# Reconstruir la base desde cero (DESTRUCTIVO)
C:\xampp\php\php.exe bin\migrate.php fresh --force --seed
```

### Buenas prácticas

- Las migraciones usan `CREATE TABLE IF NOT EXISTS`, por lo que se pueden aplicar sobre una base ya existente sin borrar datos. En ese caso quedan registradas como aplicadas en el lote 1.
- `fresh` elimina **todas** las tablas y vistas de la base configurada; úsalo solo en desarrollo y siempre con `--force`.
- Los seeders usan `INSERT IGNORE` con identificadores explícitos, así que son seguros de re-ejecutar.
- Para agregar un cambio de esquema, crea un nuevo archivo en `database/migrations` con un nombre posterior y su `.down.sql` correspondiente. No edites migraciones ya aplicadas.
- La carpeta `database/` y `bin/` están bloqueadas tanto en Apache (`.htaccess`) como en el servidor embebido (`router.php`).

## Configuración

`app/config.php`:

```php
'app' => [
    'name'         => 'Sistema Escolar',
    'timezone'     => 'America/Mexico_City',
    'debug'        => true,   // false en producción
    'pretty_urls'  => true,
    'per_page'     => 10,     // filas por página en las vistas
    'api_per_page' => 25,     // filas por página en la API
],
'db' => [
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'escuela',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
],
'auth' => [
    'cookie'          => 'escuela_admin_token', // cookie del token web
    'token_days'      => 30,                    // vigencia de cada token
    'max_attempts'    => 5,                     // intentos fallidos permitidos
    'lockout_minutes' => 15,                    // bloqueo temporal del formulario
    'password_min'    => 8,                     // longitud mínima de contraseña
],
```

También puedes usar variables de entorno: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_DEBUG`, `APP_PRETTY_URLS`, `APP_TIMEZONE`, `AUTH_COOKIE`, `AUTH_TOKEN_DAYS`, `AUTH_MAX_ATTEMPTS`, `AUTH_LOCKOUT_MINUTES` y `AUTH_PASSWORD_MIN`.

## Estructura del proyecto

```
├── index.php                 # Front controller
├── router.php                # Router para `php -S`
├── .htaccess                 # URLs limpias con Apache
├── escuela.sql               # Esquema clásico (alternativo a las migraciones)
├── bin/
│   ├── migrate.php           # Ejecutor de migraciones y seeders
│   └── admin.php             # Alta y gestión de administradores y tokens
├── database/
│   ├── migrations/           # Migraciones .sql y sus reversos .down.sql
│   └── seeders/              # Datos de ejemplo idempotentes
├── docs/
│   └── openapi.json          # Especificación OpenAPI 3.0.3
├── app/
│   ├── bootstrap.php         # Autoloader, configuración y sesión
│   ├── config.php            # Configuración de app, base de datos y auth
│   ├── resources.php         # Definición declarativa de los CRUD
│   ├── routes.php            # Rutas HTML y API
│   ├── helpers.php           # Funciones de apoyo y presentación
│   ├── Core/                 # Router, Request, Response, Database, Validator, etc.
│   ├── Controllers/          # Dashboard, Resource, Boleta, Api, Auth y Account
│   ├── Services/             # Lógica de boletas y autenticación
│   └── Views/                # Layout, dashboard, recursos, boletas, auth y errores
└── assets/
    ├── css/app.css
    ├── js/app.js
    └── vendor/swagger-ui/    # Swagger UI servido localmente
```

### Agregar un campo a un CRUD

Casi todo es declarativo. Por ejemplo, para agregar un campo a `alumnos`:

1. Crea una migración que agregue la columna.
2. Añade su definición en `app/resources.php` dentro de `fields` (etiqueta, tipo y reglas).
3. Si quieres mostrarlo en la tabla, agrégalo a `list`.

La vista, el formulario, la validación y la API lo toman automáticamente.

## Autenticación y administradores

El sistema **no incluye credenciales por defecto**. Las cuentas se crean desde la consola.

### Inicio de sesión web

- `/login` es la única página HTML pública (la documentación de la API también es pública).
- Al autenticarte se crea un **token por dispositivo**, se guarda su hash SHA-256 en `admin_tokens` y se envía al navegador en una cookie `HttpOnly` + `SameSite=Lax`.
- Cada dispositivo tiene su propia sesión. Desde **Mi cuenta** (`/cuenta`) puedes consultar IP, último uso y expiración, revocar dispositivos individualmente o cerrar todos los demás.
- Al cambiar la contraseña se revocan las sesiones de los demás dispositivos.
- Tras 5 intentos fallidos el formulario se bloquea 15 minutos (`auth.max_attempts` y `auth.lockout_minutes`).

### Comandos de administración

```bash
# Listar cuentas y tokens activos
C:\xampp\php\php.exe bin\admin.php list

# Crear una cuenta (contraseña generada) o con contraseña explícita
C:\xampp\php\php.exe bin\admin.php create admin --nombre="Administrador" --correo=admin@escuela.mx
C:\xampp\php\php.exe bin\admin.php create soporte --password="ClaveSegura2026!"

# Cambiar contraseña (revoca todos sus tokens)
C:\xampp\php\php.exe bin\admin.php password admin

# Activar / desactivar una cuenta (desactivar revoca sus tokens)
C:\xampp\php\php.exe bin\admin.php disable soporte
C:\xampp\php\php.exe bin\admin.php enable soporte

# Consultar y revocar tokens por dispositivo
C:\xampp\php\php.exe bin\admin.php tokens admin
C:\xampp\php\php.exe bin\admin.php revoke 12
C:\xampp\php\php.exe bin\admin.php revoke-all admin
```

### Seguridad

- Solo se almacena el hash SHA-256 del token; el valor original se muestra una única vez al iniciar sesión.
- Las contraseñas se guardan con `password_hash()` y se verifican con `password_verify()`.
- Los tokens expiran a los 30 días (`auth.token_days`) y los revocados dejan de funcionar de inmediato.
- Las rutas HTML redirigen a `/login?next=...`; las rutas de la API responden `401` con `WWW-Authenticate: Bearer`.
- La API **solo** acepta `Authorization: Bearer <token>`: la cookie de sesión web no autentica rutas `/api/*`.
- En Apache, la cabecera `Authorization` se conserva mediante `.htaccess` (mod_rewrite/mod_setenvif).
- `POST /api/login`, `/api/docs` y `/api/openapi.json` son públicas.
- En producción usa **HTTPS**: la cookie se marca `Secure` automáticamente y el token viaja cifrado.

## Documentación de la API (Swagger UI)

La API incluye documentación interactiva basada en una especificación **OpenAPI 3.0.3**:

| Recurso | URL |
| --- | --- |
| Swagger UI | `/api/docs` |
| Especificación OpenAPI | `/api/openapi.json` |

Abre `http://localhost:8000/api/docs` (o `http://localhost/escuela-crud/api/docs` con Apache). Desde ahí puedes consultar todos los endpoints, revisar los esquemas de cada recurso y probar las peticiones con **Try it out**.

- La especificación vive en `docs/openapi.json`.
- Swagger UI se sirve localmente desde `assets/vendor/swagger-ui`, así que la documentación funciona sin conexión a internet.
- Para probar rutas protegidas, primero ejecuta `POST /api/login`, copia el `token` y pégalo en **Authorize** (esquema `bearerAuth`). La autorización se conserva entre recargas.
- Si agregas o cambias endpoints, actualiza `docs/openapi.json`.

## API JSON

Base: `http://localhost/escuela-crud/api`

Todas las rutas requieren `Authorization: Bearer <token>`, salvo `POST /api/login` (y la documentación). Sin un token Bearer válido la API responde `401`; la cookie de sesión web no se acepta en `/api/*`.

| Método | Ruta | Descripción |
| --- | --- | --- |
| `POST` | `/api/login` | Obtener un token para el dispositivo |
| `POST` | `/api/logout` | Revocar el token actual |
| `GET` | `/api/me` | Administrador y token actuales |
| `GET` | `/api` | Índice de recursos y endpoints |
| `GET` | `/api/stats` | Totales generales |
| `GET` | `/api/boleta?matricula=A-2025-001&ciclo=2025-2026` | Boleta y resumen |
| `GET` | `/api/alumnos` | Listar (paginado) |
| `POST` | `/api/alumnos` | Crear |
| `GET` | `/api/alumnos/{id}` | Ver uno |
| `PUT`/`PATCH` | `/api/alumnos/{id}` | Actualizar (PATCH permite envíos parciales) |
| `DELETE` | `/api/alumnos/{id}` | Eliminar |

Los mismos endpoints existen para `profesores`, `grupos`, `materias` e `inscripciones`.

### Parámetros de listado

- `page` (por defecto `1`)
- `per_page` (máximo `100`)
- `q` (búsqueda de texto)
- `sort` y `dir` (`asc`/`desc`) sobre columnas permitidas
- Filtros del recurso: `grupo_id`, `activo`, `turno`, `profesor_id`, `activa`, `estado`, `ciclo_escolar`, etc.

### Ejemplos

```bash
# 1. Iniciar sesión y guardar el token (requiere jq)
TOKEN=$(curl -s -X POST "http://localhost/escuela-crud/api/login" \
  -H "Content-Type: application/json" \
  -d '{"usuario": "admin", "password": "TuClaveSegura2026!", "dispositivo": "Script de reportes"}' \
  | jq -r '.token')

# 2. Consultar los datos del administrador y del token actual
curl "http://localhost/escuela-crud/api/me" \
  -H "Authorization: Bearer $TOKEN"

# Listar alumnos activos del grupo 1
curl "http://localhost/escuela-crud/api/alumnos?grupo_id=1&activo=1&page=1&per_page=10" \
  -H "Authorization: Bearer $TOKEN"

# Crear un alumno
curl -X POST "http://localhost/escuela-crud/api/alumnos" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "matricula": "A-2026-010",
    "nombre": "María",
    "apellido_paterno": "López",
    "apellido_materno": "García",
    "fecha_nacimiento": "2011-04-12",
    "sexo": "F",
    "correo": "maria.lopez@alumno.escuela.mx",
    "grupo_id": 1,
    "activo": 1
  }'

# Actualización parcial
curl -X PATCH "http://localhost/escuela-crud/api/alumnos/3" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"telefono": "5551234567"}'

# Eliminar
curl -X DELETE "http://localhost/escuela-crud/api/alumnos/3" \
  -H "Authorization: Bearer $TOKEN"

# Boleta
curl "http://localhost/escuela-crud/api/boleta?matricula=A-2025-001&ciclo=2025-2026" \
  -H "Authorization: Bearer $TOKEN"

# Cerrar sesión (revoca el token)
curl -X POST "http://localhost/escuela-crud/api/logout" \
  -H "Authorization: Bearer $TOKEN"
```

### Respuestas y errores

- `200` operación correcta.
- `201` recurso creado (incluye cabecera `Location`).
- `401` token ausente, inválido, expirado o revocado.
- `404` recurso o registro inexistente.
- `405` método no permitido.
- `422` validación fallida. Devuelve `errors` por campo:

```json
{
  "error": true,
  "status": 422,
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "matricula": "Este campo es obligatorio.",
    "correo": "Debe ser un correo electrónico válido."
  }
}
```

## Notas

- La tabla `alumnos_anterior` no se incluye en el CRUD porque parece ser un respaldo/importación histórica y no forma parte del modelo relacional actual. Se conserva en las migraciones para poder reconstruir la base completa.
- Las cuentas de administrador viven en `admins` y los tokens por dispositivo en `admin_tokens` (migraciones `000008` y `000009`). Desactivar una cuenta o cambiar su contraseña revoca sus tokens.
- En producción cambia `debug` a `false` y usa un usuario de MySQL con permisos mínimos.
- Si despliegas en un servidor donde la carpeta `app/` es accesible por URL, asegúrate de que se aplique su `.htaccess` (o mueve el proyecto fuera del `DocumentRoot`).
