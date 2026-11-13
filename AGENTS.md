# AGENTS.md

Guía de trabajo para agentes de IA y colaboradores del **Sistema Escolar** (PHP vanilla + MariaDB/MySQL).
Todo el código, la documentación y la interfaz están en **español**.

---

## 1. Regla obligatoria: la API y Swagger se actualizan juntos

> **Ningún cambio de API se considera terminado si `docs/openapi.json` no se actualiza en el mismo cambio.**

Esto aplica **siempre** que se añada, edite o elimine un endpoint, o que cambie su contrato:

| Cambio realizado | Actualización obligatoria en `docs/openapi.json` |
| --- | --- |
| Añadir endpoint o método HTTP | Nuevo `path` + operación con `operationId`, `summary`, `tags`, `parameters`, `requestBody`, `responses` y `security` si aplica |
| Editar endpoint | Actualizar parámetros, cuerpo, esquemas, códigos de respuesta, ejemplos y descripción |
| Eliminar endpoint | Borrar el `path`/método y los esquemas que queden huérfanos |
| Cambiar campos o validaciones | Actualizar el esquema `X`, `XWrite`, `XCreate`, `XUpdate` y los ejemplos |
| Cambiar autenticación | Actualizar `securitySchemes`, `security` global y las excepciones (`security: []`) |
| Añadir un recurso CRUD | Documentar los 6 endpoints (`list`, `create`, `show`, `update`, `patch`, `delete`) y sus 4 esquemas |

### Definición de terminado para un cambio de API

1. Registrar o modificar la ruta en `app/routes.php`.
2. Implementar el comportamiento en `app/Controllers/ApiController.php` (o `ApiAuthController` para auth) y, si aplica, en `Repository`/`Validator`/`Resources`.
3. **Actualizar `docs/openapi.json` en el mismo cambio.**
4. Si el cambio es visible para el usuario de la API, actualizar también la tabla y los ejemplos de `README.md`.
5. Validar el JSON y comprobar que no haya `operationId` duplicados ni `$ref` rotos (ver §12).
6. Probar el endpoint con `curl` o Swagger UI.

### Convenciones de `docs/openapi.json`

- Especificación **OpenAPI 3.0.3** en un único archivo `docs/openapi.json`.
- El servidor usa el marcador `"url": "__BASE_URL__"`; `DocsController::spec()` lo reemplaza en tiempo de ejecución por la ruta base real. **No lo sustituyas por una URL fija.**
- `security` global: `bearerAuth`. Las rutas públicas deben declarar `"security": []`.
- Endpoints públicos reales: `POST /api/login`, `GET /api/docs` y `GET /api/openapi.json`.
  - `/api/docs` y `/api/openapi.json` no se documentan como operaciones de negocio.
- `operationId` único y descriptivo:
  - Autenticación: `apiLogin`, `apiLogout`, `apiMe`.
  - Sistema: `apiIndex`, `apiStats`, `apiBoleta`.
  - Recursos: `listAlumnos`, `createAlumno`, `showAlumno`, `updateAlumno`, `patchAlumno`, `deleteAlumno` (mismo patrón para `Profesores`, `Grupos`, `Materias`, `Inscripciones`).
- Esquemas por recurso:
  - `X`: representación de lectura.
  - `XWrite`: campos comunes de escritura.
  - `XCreate`: cuerpo de creación (hereda de `XWrite`).
  - `XUpdate`: cuerpo de actualización total.
- Respuestas compartidas en `components.responses`: `Unauthorized`, `NotFound`, `MethodNotAllowed`, `ValidationFailed`, `ServerError`.
- Errores de validación: HTTP `422` con objeto `errors` por campo.
- Todos los textos (`summary`, `description`, ejemplos) en español.

---

## 2. Entorno de desarrollo

- **SO:** Windows. PHP **no** está en `PATH`; usa la ruta completa.
- **PHP:** `C:\xampp\php\php.exe` (PHP 8.2.x, extensiones `pdo_mysql`, `mbstring`).
- **MySQL/MariaDB CLI:** `C:\xampp\mysql\bin\mysql.exe`.
- **Sin Composer, sin npm, sin CDN.** No agregues dependencias externas.

### Arrancar el servidor embebido

```powershell
C:\xampp\php\php.exe -S localhost:8000 router.php
```

Abrir `http://localhost:8000/login`. El router bloquea `/app`, `/bin`, `/database`, `/docs` y archivos ocultos.

### Base de datos

Valores por defecto en `app/config.php`:

| Variable | Valor |
| --- | --- |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `escuela` |
| `DB_USER` | `root` |
| `DB_PASS` | vacío |

También se puede configurar con `APP_DEBUG`, `APP_PRETTY_URLS`, `APP_TIMEZONE`, `AUTH_COOKIE`, `AUTH_TOKEN_DAYS`, `AUTH_MAX_ATTEMPTS`, `AUTH_LOCKOUT_MINUTES`, `AUTH_PASSWORD_MIN`.

### Comandos habituales

```powershell
# Migraciones
C:\xampp\php\php.exe bin\migrate.php status
C:\xampp\php\php.exe bin\migrate.php migrate
C:\xampp\php\php.exe bin\migrate.php migrate --seed
C:\xampp\php\php.exe bin\migrate.php rollback
C:\xampp\php\php.exe bin\migrate.php fresh --force --seed

# Administradores
C:\xampp\php\php.exe bin\admin.php list
C:\xampp\php\php.exe bin\admin.php create <usuario> --nombre="..." --correo="..."
C:\xampp\php\php.exe bin\admin.php password <usuario>
C:\xampp\php\php.exe bin\admin.php disable <usuario>
C:\xampp\php\php.exe bin\admin.php enable <usuario>
C:\xampp\php\php.exe bin\admin.php tokens <usuario>
C:\xampp\php\php.exe bin\admin.php revoke <token_id>
C:\xampp\php\php.exe bin\admin.php revoke-all <usuario>
```

---

## 3. Estructura completa del proyecto

```text
.
├── index.php                     # Front controller: carga bootstrap y ejecuta Application
├── router.php                    # Router para `php -S`; bloquea carpetas internas y sirve assets
├── .htaccess                     # URLs limpias y reenvío de Authorization en Apache
├── escuela.sql                   # Esquema clásico alternativo a las migraciones
├── README.md                     # Guía de instalación, uso, API y autenticación
├── AGENTS.md                     # Este archivo
│
├── app/
│   ├── bootstrap.php             # BASE_PATH, autoloader PSR-4, configuración, zona horaria, sesión
│   ├── config.php                # Configuración de app, base de datos y autenticación
│   ├── resources.php             # Definición declarativa de los 5 CRUD
│   ├── routes.php                # Registro de rutas HTML y API
│   ├── helpers.php               # e(), url(), csrf_field(), formatos, celdas de tabla, etc.
│   ├── .htaccess                 # Bloquea el acceso web directo a app/
│   │
│   ├── Core/
│   │   ├── Application.php       # Arranque, rutas públicas, guard de auth, manejo de errores
│   │   ├── Config.php            # Configuración global con notación de puntos
│   │   ├── Csrf.php              # Token y validación CSRF para formularios HTML
│   │   ├── Database.php          # PDO singleton y helpers query/all/one/value/insert/update/delete
│   │   ├── HttpException.php     # Excepción con código HTTP
│   │   ├── Migrator.php          # Motor de migraciones, rollback, seed, fresh y status
│   │   ├── Repository.php        # Consultas del CRUD: find, paginate, create, update, delete, options
│   │   ├── Request.php           # Método, ruta, cuerpo JSON/form y query string
│   │   ├── Resources.php         # Acceso a las definiciones de app/resources.php
│   │   ├── Response.php          # Respuestas JSON y redirecciones
│   │   ├── Router.php            # Registro y despacho de rutas GET/POST/PUT/PATCH/DELETE
│   │   ├── Session.php           # Sesión PHP, mensajes flash
│   │   ├── Validator.php         # Reglas: required, max, min, email, date, in, exists, unique, etc.
│   │   └── View.php              # Render de vistas y partials, con modo standalone
│   │
│   ├── Controllers/
│   │   ├── AccountController.php # Mi cuenta: perfil, cambio de contraseña, dispositivos
│   │   ├── ApiAuthController.php # POST /api/login, POST /api/logout, GET /api/me
│   │   ├── ApiController.php     # CRUD JSON, índice, stats y boleta
│   │   ├── AuthController.php    # Login/logout web, CSRF, bloqueo por intentos
│   │   ├── BoletaController.php  # Vista HTML de boletas
│   │   ├── DashboardController.php # Panel inicial con totales
│   │   ├── DocsController.php    # Swagger UI y entrega de openapi.json
│   │   └── ResourceController.php # CRUD HTML genérico para los 5 recursos
│   │
│   ├── Services/
│   │   ├── Auth.php              # Tokens por dispositivo, cookies, hashes, resolución de sesión
│   │   └── BoletaService.php     # Consulta de vista_boleta, filtros y resumen
│   │
│   └── Views/
│       ├── layout.php            # Layout autenticado: navegación, usuario, flashes
│       ├── dashboard.php         # Panel inicial
│       ├── boleta.php            # Boleta imprimible
│       ├── swagger.php           # Contenedor de Swagger UI
│       ├── account/index.php     # Perfil, contraseña y dispositivos
│       ├── auth/login.php        # Login independiente (sin layout autenticado)
│       ├── errors/error.php      # Página de errores
│       └── resources/
│           ├── index.php         # Listado con búsqueda, filtros, orden y paginación
│           ├── form.php          # Alta y edición
│           ├── show.php          # Detalle
│           ├── _field.php        # Render de cada tipo de campo
│           └── _pagination.php   # Paginación reutilizable
│
├── assets/
│   ├── css/app.css               # Estilos generales e impresión
│   ├── js/app.js                 # Interacciones de la interfaz
│   └── vendor/swagger-ui/        # Swagger UI servido localmente (sin CDN)
│
├── bin/
│   ├── .htaccess                 # Bloquea el acceso web a bin/
│   ├── admin.php                 # CLI de administradores y tokens
│   └── migrate.php               # CLI de migraciones y seeders
│
├── database/
│   ├── .htaccess                 # Bloquea el acceso web a database/
│   ├── migrations/               # Migraciones versionadas .sql y .down.sql
│   └── seeders/01_datos_iniciales.sql
│
├── docs/
│   ├── .htaccess                 # Bloquea el acceso web directo a docs/
│   └── openapi.json              # Especificación OpenAPI 3.0.3 (Swagger)
│
└── api_escuela/                  # Repositorio git anidado vacío (.git + .gitattributes).
                                  # No forma parte del runtime ni del código de la aplicación.
```

### Responsabilidad de cada capa

| Capa | Archivos | Regla |
| --- | --- | --- |
| Entrada | `index.php`, `router.php`, `.htaccess` | Solo enrutan; no contienen lógica de negocio |
| Núcleo | `app/Core/*` | Infraestructura reutilizable, sin conocimiento de recursos concretos |
| Dominio declarativo | `app/resources.php` | Fuente única de verdad de campos, reglas, listados y filtros |
| Controladores | `app/Controllers/*` | Orquestan Request → Repository/Validator → View/Response |
| Servicios | `app/Services/*` | Lógica transversal: autenticación y boletas |
| Vistas | `app/Views/*` | Solo presentación; escapan salida con `e()` |
| Datos | `database/*` | Esquema versionado y datos de ejemplo |
| Documentación | `docs/openapi.json`, `README.md` | Contrato público de la API |

---

## 4. Flujo de una petición

```text
Petición HTTP
  → index.php
  → app/bootstrap.php (autoloader, config, sesión)
  → App\Core\Application::run()
      → Request::path() y detección de /api
      → guard de autenticación (rutas públicas exceptuadas)
      → app/routes.php
      → Router::dispatch()
      → Controlador
          → Validator (HTML y API)
          → Repository (PDO con sentencias preparadas)
          → View::render() o Response::json()
  → manejo centralizado de HttpException / Throwable
```

Rutas públicas (`Application::PUBLIC_PATHS`): `/login`, `/api/login`, `/api/docs`, `/api/openapi.json`.
Todo lo demás exige autenticación:

- HTML → redirección a `/login?next=...`.
- API → `401` JSON con `WWW-Authenticate: Bearer`.
- La API **solo** acepta `Authorization: Bearer <token>`; la cookie web no autentica `/api/*`.

---

## 5. CRUD declarativo (`app/resources.php`)

Cada recurso (`alumnos`, `profesores`, `grupos`, `materias`, `inscripciones`) define:

| Clave | Uso |
| --- | --- |
| `table`, `primary_key` | Tabla y llave primaria |
| `singular`, `plural`, `icon`, `description` | Textos e interfaz |
| `default_order` | Orden por defecto |
| `search` | Columnas para `q` |
| `sortable` | Columnas permitidas en `sort` |
| `list` | Columnas visibles en el listado |
| `filters` | Filtros exactos disponibles |
| `fields` | Definición de campos: `label`, `type`, `rules`, `unique`, `cast`, `default`, `options`, `relation`, `wide`, etc. |
| `virtual` | Columnas calculadas o derivadas de relaciones |
| `unique_groups` | Llaves compuestas únicas |

### Añadir un campo a un recurso

1. Crear una migración nueva que agregue la columna (con su `.down.sql`).
2. Agregar el campo en `fields` dentro de `app/resources.php`.
3. Si debe verse en la tabla, agregarlo a `list`.
4. Si aplica, actualizar los esquemas y ejemplos de `docs/openapi.json`.
5. Verificar formulario HTML, validación y API.

Las vistas, la validación, los filtros y la API se generan a partir de esta definición. Evita duplicar reglas en controladores.

---

## 6. Rutas

### HTML

| Método | Ruta | Acción |
| --- | --- | --- |
| `GET` | `/login` | Formulario de acceso |
| `POST` | `/login` | Autenticar |
| `POST` | `/logout` | Cerrar sesión web |
| `GET` | `/cuenta` | Perfil y dispositivos |
| `POST` | `/cuenta/password` | Cambiar contraseña |
| `POST` | `/cuenta/dispositivos/revocar-otras` | Revocar otros dispositivos |
| `POST` | `/cuenta/dispositivos/{id}/revocar` | Revocar un dispositivo |
| `GET` | `/`, `/inicio` | Panel |
| `GET` | `/boleta` | Boletas |
| `GET` | `/{recurso}` | Listado |
| `GET` | `/{recurso}/nuevo` | Alta |
| `POST` | `/{recurso}` | Guardar |
| `GET` | `/{recurso}/{id}` | Detalle |
| `GET` | `/{recurso}/{id}/editar` | Edición |
| `POST` | `/{recurso}/{id}` | Actualizar |
| `POST` | `/{recurso}/{id}/eliminar` | Eliminar |

### API

| Método | Ruta | Acción |
| --- | --- | --- |
| `POST` | `/api/login` | Token del dispositivo (público) |
| `POST` | `/api/logout` | Revocar token actual |
| `GET` | `/api/me` | Administrador y token actuales |
| `GET` | `/api` | Índice de recursos |
| `GET` | `/api/stats` | Totales |
| `GET` | `/api/boleta` | Boleta y resumen |
| `GET` | `/api/{recurso}` | Listar (paginado) |
| `POST` | `/api/{recurso}` | Crear |
| `GET` | `/api/{recurso}/{id}` | Ver |
| `PUT`/`PATCH` | `/api/{recurso}/{id}` | Actualizar |
| `DELETE` | `/api/{recurso}/{id}` | Eliminar |
| `GET` | `/api/docs` | Swagger UI (público) |
| `GET` | `/api/openapi.json` | Especificación (público) |

Reglas:

- Las rutas fijas de `/api/*` se registran antes de las rutas con `{recurso}`.
- `PUT` exige el recurso completo; `PATCH` permite envíos parciales.
- Los formularios pueden usar `_method` o `X-HTTP-Method-Override`.
- Un método no permitido devuelve `405`.

---

## 7. Convenciones de la API

- JSON UTF-8 en peticiones y respuestas.
- Autenticación: `Authorization: Bearer <token>`.
- Listados: `page`, `per_page` (máx. 100), `q`, `sort`, `dir` y filtros del recurso.
- Respuestas correctas: `200`; creación: `201` con cabecera `Location`; eliminación: `200`.
- Errores: `401`, `404`, `405`, `422` y `500` con el formato:

```json
{
  "error": true,
  "status": 422,
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "campo": "Mensaje en español."
  }
}
```

- No expongas trazas ni detalles internos con `app.debug = false`.
- No agregues endpoints sin documentarlos en `docs/openapi.json`.

---

## 8. Base de datos y migraciones

Tablas principales: `profesores`, `grupos`, `alumnos`, `materias`, `inscripciones`, `admins`, `admin_tokens`.
Vista: `vista_boleta`. Control interno: `migraciones`.
`alumnos_anterior` es un respaldo histórico: **no** se incluye en el CRUD, pero sí en las migraciones.

| Migración | Contenido |
| --- | --- |
| `2026_11_06_000001` | `profesores` |
| `2026_11_06_000002` | `grupos` |
| `2026_11_06_000003` | `alumnos` |
| `2026_11_06_000004` | `materias` |
| `2026_11_06_000005` | `inscripciones` |
| `2026_11_06_000006` | Vista `vista_boleta` |
| `2026_11_06_000007` | `alumnos_anterior` (histórico) |
| `2026_11_13_000008` | `admins` |
| `2026_11_13_000009` | `admin_tokens` |

Reglas:

- Nombre: `AAAA_MM_DD_NNNNNN_descripcion.sql` + su `...down.sql`.
- **Nunca edites una migración ya aplicada**; crea una nueva.
- Usa `IF NOT EXISTS` / `IF EXISTS` cuando tenga sentido.
- Mantén los seeders idempotentes (`INSERT IGNORE` con IDs explícitos).
- `fresh` es destructivo: úsalo solo en desarrollo y con `--force`.

---

## 9. Autenticación y seguridad

- Sin credenciales por defecto. Las cuentas se crean con `bin/admin.php`.
- Contraseñas con `password_hash()` y `password_verify()`; mínimo 8 caracteres.
- Tokens por dispositivo: se guarda **solo** el hash SHA-256 en `admin_tokens`.
- El token original se muestra una sola vez al iniciar sesión.
- Cookie web: `escuela_admin_token`, `HttpOnly`, `SameSite=Lax`, `Secure` automático en HTTPS.
- Expiración por defecto: 30 días.
- Revocar un token o desactivar una cuenta invalida el acceso de inmediato.
- Cambiar la contraseña revoca los demás tokens.
- Formularios HTML: CSRF obligatorio.
- Login web: bloqueo tras 5 intentos fallidos durante 15 minutos.
- Consultas: PDO con sentencias preparadas. Nunca concatenes datos del usuario.
- Escapa toda salida HTML con `e()`.
- No registres contraseñas ni tokens en logs.

---

## 10. Vistas y assets

- `app/Views/layout.php` envuelve las páginas autenticadas.
- `auth/login.php` y `errors/error.php` se renderizan en modo `standalone`.
- Los formularios incluyen `csrf_field()`.
- Los listados reutilizan `resources/index.php` y `_pagination.php`.
- Los campos se renderizan desde `resources/_field.php` según `type`.
- Estilos en `assets/css/app.css`; comportamiento en `assets/js/app.js`.
- Swagger UI se sirve localmente desde `assets/vendor/swagger-ui`; no uses CDN.
- Mantén la interfaz en español y adaptable a móvil.

---

## 11. Convenciones de código

- PHP 8, `declare(strict_types=1);` en todos los archivos.
- Namespaces `App\...` con autoloader PSR-4 (`App\Core\Database` → `app/Core/Database.php`).
- Controladores delgados; la lógica de datos va en `Repository`, la validación en `Validator`, la definición en `resources.php`.
- Nombres de clases, métodos y variables en inglés; textos de interfaz y mensajes en español.
- Sin frameworks, sin Composer, sin dependencias externas.
- No dupliques reglas de validación entre HTML y API: ambas usan la misma definición.
- No agregues SQL dinámico con identificadores no validados; usa listas blancas (`sortable`, `filters`).
- No dejes código de depuración, `var_dump`, credenciales ni tokens en el repositorio.

---

## 12. Verificación antes de entregar

### Sintaxis PHP

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
    & C:\xampp\php\php.exe -l $_.FullName
}
```

### Estado de la base de datos

```powershell
C:\xampp\php\php.exe bin\migrate.php status
```

### Validar `docs/openapi.json`

```powershell
$raw = Get-Content docs\openapi.json -Raw
$spec = $raw | ConvertFrom-Json

if ($spec.openapi -ne '3.0.3') { throw 'La versión de OpenAPI debe ser 3.0.3' }

$ops = @()
foreach ($path in $spec.paths.PSObject.Properties) {
    foreach ($method in $path.Value.PSObject.Properties) {
        if ($method.Name -in @('get','post','put','patch','delete')) {
            if (-not $method.Value.operationId) { throw "Falta operationId en $($method.Name.ToUpper()) $($path.Name)" }
            $ops += [pscustomobject]@{
                Endpoint = "$($method.Name.ToUpper()) $($path.Name)"
                Id       = $method.Value.operationId
            }
        }
    }
}

$duplicados = $ops | Group-Object Id | Where-Object Count -gt 1
if ($duplicados) { throw "operationId duplicados: $($duplicados.Name -join ', ')" }

"Operaciones documentadas: $($ops.Count)"
```

Comprobar además:

- Todos los `$ref` apuntan a un esquema, respuesta o parámetro existente.
- El marcador `__BASE_URL__` sigue presente exactamente en `servers`.
- Cada endpoint nuevo tiene al menos una respuesta de éxito y las respuestas de error aplicables.

### Prueba funcional de la API

```powershell
# Guarda el cuerpo en un archivo para evitar problemas de comillas en PowerShell.
'{"usuario":"admin","password":"TU_CONTRASEÑA"}' |
    Set-Content "$env:TEMP\login.json" -NoNewline -Encoding ascii

$login = curl.exe -s -X POST http://localhost:8000/api/login `
    -H "Content-Type: application/json" --data-binary "@$env:TEMP\login.json" |
    ConvertFrom-Json

$headers = @{ Authorization = "Bearer $($login.token)" }
curl.exe -s http://localhost:8000/api/me -H "Authorization: Bearer $($login.token)"
```

> En PowerShell evita JSON en línea con `\"`: el cuerpo puede llegar truncado. Usa un archivo con `--data-binary "@archivo"` o `ConvertTo-Json`.

### Limpieza

- Revoca los tokens de prueba para no dejar sesiones activas:

```powershell
C:\xampp\php\php.exe bin\admin.php revoke-all <usuario>
```

- Elimina archivos temporales y cualquier script de depuración.

---

## 13. No hacer

- No modificar la API sin actualizar `docs/openapi.json`.
- No eliminar un endpoint de Swagger sin eliminarlo también de `app/routes.php` (y viceversa).
- No romper la publicación de `/api/docs` ni `/api/openapi.json`.
- No aceptar la cookie web como autenticación de `/api/*`.
- No crear administradores ni contraseñas por defecto en migraciones o seeders.
- No editar migraciones ya aplicadas.
- No exponer `app/`, `bin/`, `database/`, `docs/` por HTTP.
- No introducir frameworks, Composer, npm ni CDN.
- No usar SQL concatenado con datos del usuario.
- No dejar tokens, contraseñas o datos personales en logs, commits o documentación.
- No tocar `api_escuela/` salvo que el usuario lo indique: es un repositorio anidado vacío.
