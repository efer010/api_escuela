# Sistema Escolar · CRUD + API (PHP vanilla)

CRUD completo sobre la base de datos `escuela` (MariaDB/MySQL) usando **PHP 8 sin frameworks ni dependencias**. Incluye vistas HTML renderizadas en servidor y una **API JSON** con las mismas reglas de validación.

## Funcionalidades

- CRUD de **alumnos, profesores, grupos, materias e inscripciones**.
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
- La base de datos `escuela` creada con las tablas del archivo `escuela.sql`.

## Instalación

### Opción A · XAMPP con Apache (recomendada)

1. Copia la carpeta del proyecto dentro de `C:\xampp\htdocs\escuela-crud`.
2. Inicia **Apache** y **MySQL** desde el panel de XAMPP.
3. Importa la base de datos si aún no existe:

   ```bash
   C:\xampp\mysql\bin\mysql.exe -u root < escuela.sql
   ```

4. Ajusta las credenciales si es necesario en `app/config.php` (por defecto `root` sin contraseña).
5. Abre `http://localhost/escuela-crud/`.

> El proyecto incluye `.htaccess`, así que las URLs limpias funcionan si Apache tiene `mod_rewrite` y `AllowOverride All` habilitados.

### Opción B · Servidor embebido de PHP

```bash
C:\xampp\php\php.exe -S localhost:8000 router.php
```

Luego abre `http://localhost:8000/`.

### Si las URLs limpias dan 404

Edita `app/config.php` y cambia:

```php
'pretty_urls' => false,
```

Con eso el sistema usará URLs del tipo `index.php?r=alumnos`. Todo seguirá funcionando.

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
```

También puedes usar variables de entorno: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_DEBUG`, `APP_PRETTY_URLS`, `APP_TIMEZONE`.

## Estructura del proyecto

```
├── index.php                 # Front controller
├── router.php                # Router para `php -S`
├── .htaccess                 # URLs limpias con Apache
├── escuela.sql               # Esquema de la base de datos
├── app/
│   ├── bootstrap.php         # Autoloader, configuración y sesión
│   ├── config.php            # Configuración de app y base de datos
│   ├── resources.php         # Definición declarativa de los CRUD
│   ├── routes.php            # Rutas HTML y API
│   ├── helpers.php           # Funciones de apoyo y presentación
│   ├── Core/                 # Router, Request, Response, Database, Validator, etc.
│   ├── Controllers/          # Dashboard, Resource, Boleta y Api
│   ├── Services/             # Lógica de boletas
│   └── Views/                # Layout, dashboard, recursos, boletas y errores
└── assets/
    ├── css/app.css
    └── js/app.js
```

### Agregar un campo a un CRUD

Casi todo es declarativo. Por ejemplo, para agregar un campo a `alumnos`:

1. Agrega la columna en la base de datos.
2. Añade su definición en `app/resources.php` dentro de `fields` (etiqueta, tipo y reglas).
3. Si quieres mostrarlo en la tabla, agrégalo a `list`.

La vista, el formulario, la validación y la API lo toman automáticamente.

## API JSON

Base: `http://localhost/escuela-crud/api`

| Método | Ruta | Descripción |
| --- | --- | --- |
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
# Listar alumnos activos del grupo 1
curl "http://localhost/escuela-crud/api/alumnos?grupo_id=1&activo=1&page=1&per_page=10"

# Crear un alumno
curl -X POST "http://localhost/escuela-crud/api/alumnos" \
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
  -H "Content-Type: application/json" \
  -d '{"telefono": "5551234567"}'

# Eliminar
curl -X DELETE "http://localhost/escuela-crud/api/alumnos/3"

# Boleta
curl "http://localhost/escuela-crud/api/boleta?matricula=A-2025-001&ciclo=2025-2026"
```

### Respuestas y errores

- `200` operación correcta.
- `201` recurso creado (incluye cabecera `Location`).
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

- La tabla `alumnos_anterior` no se incluye en el CRUD porque parece ser un respaldo/importación histórica y no forma parte del modelo relacional actual.
- En producción cambia `debug` a `false` y usa un usuario de MySQL con permisos mínimos.
- Si despliegas en un servidor donde la carpeta `app/` es accesible por URL, asegúrate de que se aplique su `.htaccess` (o mueve el proyecto fuera del `DocumentRoot`).
