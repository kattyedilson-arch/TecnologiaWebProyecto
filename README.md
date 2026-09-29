# G-7 Gestion — Sistema de Tutorías UPDS

Gestión de tutorías para la Universidad Privada de Datos Seguros (UPDS). Permite a
estudiantes solicitar horas de tutoría, a tutores administrar su disponibilidad y al
personal administrativo(admin, Modalidades de Grado) llevar el control académico.

## Stack

| Capa | Tecnología |
|------|------------|
| Lenguaje | PHP 8.2 (Apache, `mod_rewrite`) |
| Base de datos | MySQL 8.0 (PDO, conexiones persistentes) |
| Front-end | Bootstrap 5.3.3 + Bootstrap Icons 1.11.3 + SweetAlert2 11 |
| Entorno | Docker + Docker Compose |

## Puesta en marcha

```bash
docker compose up -d --build
docker compose exec -T web php scripts/migrar.php
```

| Servicio | URL |
|----------|-----|
| Aplicación | http://localhost:8010 |
| phpMyAdmin | http://localhost:8085 (usuario `root`) |
| MySQL | `localhost:3308` |

La configuración de conexión se lee **exclusivamente de variables de entorno**
(`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`), que `docker-compose.yml` toma de un
archivo `.env` en la raíz. No hay credenciales escritas en el código. Copia
`.env.example` a `.env` para ajustarlas.

## Usuarios de prueba

Todos usan la contraseña `password`:

| Usuario | Rol |
|---------|-----|
| `admin` | administrador |
| `tutor1`, `tutor2` | tutor |
| `estudiante1`, `estudiante2` | estudiante |

Roles disponibles en el sistema: `administrador`, `tutor`, `estudiante`,
`coordinador_mg` y `auxiliar_mg`.

> **Aviso:** este repositorio es público y estas credenciales son de solo prueba.
> Cualquiera puede entrar con ellas. No son datos reales: toda la semilla usa
> correos `@tutorias.local`.

## Migraciones de base de datos

El esquema evoluciona mediante archivos versionados en `database/migrations/`, que el
runner aplica en orden y registra en la tabla `schema_migrations` (idempotente):

```bash
docker compose exec -T web php scripts/migrar.php
```

Van de `001_baseline_actual.sql` a `018_atributos_carrera.sql` (13 archivos; el
rango 004-008 quedó reservado). Los archivos deben seguir el patrón
`NNN_descripcion.sql` y no incluir `CREATE DATABASE` ni `USE`, porque el runner ya
conecta a la base definida en `.env`. Ver `database/migrations/README.md`.

## Estructura

```
controllers/   Puntos de entrada: validan, aplican reglas y redirigen
models/        Lógica de negocio y acceso a datos (PDO)
views/         Plantillas PHP (layouts, panel de cada rol, formularios)
includes/      Utilidades compartidas: conexión, sesión, render de listados
config/        Configuración PHP y conexión
database/      init.sql (semilla) + migrations/
scripts/       Runner de migraciones
assets/        CSS, JS, imágenes y subidas de usuarios
```

## Ramas

| Rama | Contenido |
|------|-----------|
| `G-7gestion-defensa` | Versión final para la defensa |
| `G-7Gestion_Demo` | Estado de integración previo |
| `G7`, `G8`, `ai`, ... | Ramas de otros módulos y equipos |
