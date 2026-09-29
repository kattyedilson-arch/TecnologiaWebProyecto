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
| `estudiante1`, `estudiante2` | estudiante (carrera 1) |
| `estudiante15` | estudiante (carrera 2: *Ingeniería de Software*) |

Para probar el flujo de **materia ofrecida sin docente** usa `estudiante15`: en la
semilla, su horario de *Ingeniería de Software* queda publicado sin tutor asignado.

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

Van de `001_baseline_actual.sql` a `022_solicitudes_interes_oferta.sql` (16 archivos;
el rango 004-008 quedó reservado). Los archivos deben seguir el patrón
`NNN_descripcion.sql` y no incluir `CREATE DATABASE` ni `USE`, porque el runner ya
conecta a la base definida en `.env`. Ver `database/migrations/README.md`.

## Materias ofrecidas sin docente

Una oferta de horario puede quedar **abierta y sin docente asignado**: el tutor publica
su disponibilidad y esa franja se muestra en el sistema aunque todavía ningún tutor la
haya tomado (`id_tutor_assigned = NULL`). Como `tutorias.id_tutor` es `NOT NULL`, el
estudiante no puede crear una tutoría sobre un horario sin docente; lo que hace es
**pedir esa materia**.

| Paso | Qué ocurre |
|------|-----------|
| El estudiante ve la card | Solo se listan las materias de su carrera que tienen algún horario en `ofertas_admin`; el que no tenga ninguno no aparece. La que sí aparece se marca `Sin docente` y ofrece un botón **Pedir** en vez del selector de reserva. |
| Registra el pedido | Se guarda una fila en `solicitudes_interes` con estado `pendiente` y su comentario opcional. No se crea fila en `tutorias`. Reenviar el pedido actualiza el comentario en lugar de duplicarlo, y volver a pedir una materia cancelada la reactiva. |
| El administrador se entera | Recibe una notificación y ve el badge `N esperando docente` en la lista de ofertas, para saber qué horarios necesitan un tutor. |
| Un tutor acepta la oferta | Los pedidos `pendiente` pasan a `atendida` y cada estudiante recibe una notificación con el docente, el turno y la fecha. El horario ya muestra el botón de reserva normal. |
| El estudiante reserva | La tutoría se crea con el tutor asignado y entra al flujo habitual (disponibilidad, cupo y sesión grupal). |
| El tutor cancela la aceptación | La oferta vuelve a quedar sin docente, los pedidos `atendida` se reabren a `pendiente` y se avisa a los estudiantes. |
| El estudiante se arrepiente | Cancela su pedido desde el panel; la materia sale de su lista de pedidos activos. |
| El administrador cierra o elimina la oferta | Los pedidos pendientes se cancelan; si la oferta se elimina, la FK `fk_interes_oferta` (ON DELETE CASCADE) se encarga. |

El pedido es **por horario, no por materia**: `solicitudes_interes` tiene un índice
único `(id_estudiante, id_oferta)`, así que un estudiante puede estar esperando en el
turno de la mañana y haber reservado el de la tarde. Solo se muestran ofertas de la
**carrera del estudiante**.

Todo el historial de estados se ve en el panel del estudiante, en la sección
**Materias que Pediste sin Docente**.

## Materias Disponibles: qué se lista y cómo se filtra

En `controllers/tutorias_solicitar.php` las materias de la carrera se agrupan solo con
las ofertas que existen de verdad:

```php
if (empty($ofertas)) {
    continue;   // la materia no tiene ningún horario: no se muestra
}
```

El filtro de **Sistema de Estudio** es un `enum` de dos valores, no un campo de texto:

```php
$sistemasEstudio = ['presencial' => 'Presencial', 'virtual' => 'Virtual'];
```

| Aspecto | Comportamiento |
|---------|----------------|
| Opciones | Solo **Presencial** y **Virtual**, tomados de `ofertas_admin.modalidad` |
| Selección inicial | Ninguna: el input `inpSistema` arranca vacío y se ven las dos modalidades |
| Filtrado | Cada fila lleva `data-modalidad`; el JavaScript oculta las que no coinciden |
| Quitar el filtro | Se pulsa otra vez el botón activo, que se desactiva y deja el input vacío |
| Sin resultados | Se avisa en el propio filtro y se muestra "Aún no hay horarios disponibles" |
| Dato informativo | La meta "Sistema de la carrera" normaliza `carreras.sistema_estudio` a esos mismos dos valores |

El filtro corre en el navegador sobre `data-modalidad`; el servidor nunca recibe el
valor, así que la comprobación real de disponibilidad sigue siendo la del POST de
reserva. Los botones nunca se deshabilitan: una modalidad sin ofertas debe poder
seleccionarse para que el estudiante vea el aviso.

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
