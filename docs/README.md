# Sistema de Tutorías y Modalidades de Grado

**Proyecto de defesa universitaria — Área de Tecnologías Web**

Sistema web institucional que integra dos procesos administrativos universitarios en
una plataforma única: la gestión del **programa de tutorías académicas** y el
**módulo de Modalidades de Grado (MG)**. Está diseñado para los roles
institucionales (administrador, tutor, estudiante, coordinador MG y auxiliar MG)
y se ejecuta de forma reproducible sobre contenedores Docker.

---

## 1. Resumen ejecutivo

| Aspecto | Detalle |
|---|---|
| **Nombre** | Sistema de Tutorías y Modalidades de Grado |
| **Categoría** | Aplicación web institucional (MVC) |
| **Lenguaje** | PHP 8.2 |
| **Base de datos** | MySQL 8.0 (33 tablas, InnoDB, `utf8mb4`) |
| **Capa de presentación** | HTML5 + CSS3 + Vue.js 3 (CDN) |
| **Acceso a datos** | PDO con consultas preparadas |
| **Despliegue** | Docker Compose (Apache + MySQL + phpMyAdmin) |
| **Patrón de arquitectura** | MVC sin framework |
| **Roles** | 5 (administrador, tutor, estudiante, coordinador_mg, auxiliar_mg) |
| **Migraciones** | 12 archivos versionados e idempotentes |

---

## 2. Problemática que resuelve

La gestión universitaria de tutorías y de modalidades de grado se realiza
históricamente mediante planillas y procesos manuales que presentan las siguientes
deficiencias:

1. **Trazabilidad deficiente.** No existe un registro centralizado y auditable de las
   sesiones de tutoría, lo que impide verificar la asistencia efectiva y la
   realización de las sesiones.
2. **Asignación manual de horarios.** La reserva de tutores y salas se resuelve por
   coordinaciones informales, generando colisiones de agenda y subutilización de
   cupos.
3. **Duplicación de esfuerzo.** El equipo de coordinación debe mantener registros
   paralelos en planillas, correos y documentos separados.
4. **Falta de control de acceso.** La información de los estudiantes y de los
   expedientes de grado queda expuesta a personas no autorizadas.
5. **Imposibilidad de medir resultados.** No se dispone de indicadores cuantificables
   sobre el desempeño de los tutores ni sobre el avance del proceso de grado.

El sistema propuesto centraliza ambos procesos, aplica control de acceso por rol,
valida las reglas de negocio en el servidor y genera indicadores de gestión.

---

## 3. Objetivos

### 3.1 Objetivo general

Desarrollar e implantar una aplicación web institucional que gestione de forma
integrada el programa de tutorías académicas y el proceso de modalidades de grado,
garantizando la trazabilidad de las operaciones, la aplicación efectiva del control
de acceso por rol y la disponibilidad de información para la toma de decisiones.

### 3.2 Objetivos específicos

1. Modelar y construir la base de datos normalizada que sostenga ambos módulos.
2. Implementar la autenticación segura y la autorización basada en roles.
3. Automatizar el ciclo de vida de una tutoría (solicitud → confirmación → ejecución
   → realización/cancelación).
4. Automatizar el flujo de una modalidad de grado (declaración → avales → revisión →
   aprobación → expediente → acta de defensa).
5. Proporcionar un tablero de indicadores para la administración.
6. Garantizar la reproducibilidad del entorno mediante contenedores.

---

## 4. Alcance

### 4.1 Módulo de Tutorías

- Catálogos de carreras, materias, turnos y roles.
- Gestión de usuarios, tutores, estudiantes y sus perfiles académicos.
- Disponibilidad de tutores por (materia, turno).
- Publicación de ofertas de tutoría por el administrador.
- Aceptación/rechazo de ofertas por el tutor.
- Solicitud de tutoría por el estudiante sobre horarios preestablecidos.
- Gestión del ciclo de estados de la tutoría.
- Evaluación de la tutoría y ranking de tutores.
- Notificaciones automáticas a los implicados.

### 4.2 Módulo de Modalidades de Grado (MG)

- Catálogo de modalidades de grado publicables.
- Declaración de modalidad por el estudiante.
- Gestión de avales y carga de documentos.
- Integración de expedientes mediante importación masiva (CSV).
- Bandeja de trabajo y aprobación/rechazo de declaraciones.
- Asignación history de tutores de modalidad.
- Expediente por etapas (previa, MG1, MG2, finalizado).
- Tribunal (jurados) y actas de defensa con notas por jurado.
- Gestión de periodos académicos, cohortes y calendario de hitos.
- Generación de documentos oficiales con correlativo.
- Configuración de parámetros del módulo (notas mínimas, plazos).

### 4.3 Fuera de alcance

- Integración con el sistema académico institucional central.
- Notificaciones por correo electrónico o SMS (solo notificaciones in-app).
- Aplicación móvil nativa.
- Pagos y facturación.

---

## 5. Tecnologías utilizadas

| Capa | Tecnología | Versión | Justificación |
|---|---|---|---|
| Lenguaje | PHP | 8.2 | Ecosistema maduro, soporte nativo de PDO y `password_hash`. |
| Servidor web | Apache | 2.4 (imagen `php:8.2-apache`) | Contenedores oficiales, `mod_rewrite` para URLs amigables. |
| Base de datos | MySQL | 8.0 | Integridad referencial, motores InnoDB, `utf8mb4`. |
| Acceso a datos | PDO | nativo | Consultas preparadas, previene inyección SQL. |
| Contenedores | Docker / Compose | 24+ | Entorno reproducible y portable. |
| Interfaz | Vue.js | 3 (CDN) | Reactividad en el cliente sin build step. |
| Estilos | CSS3 | — | Diseño institucional responsive. |
| Versionado de esquema | SQL + runner PHP | — | Migraciones idempotentes versionadas. |

Detalle completo en [`Tecnologias.md`](Tecnologias.md).

---

## 6. Estructura del proyecto

```
TecnologiaWebProyecto/
├── index.php                  Punto de entrada (landing pública o panel por rol)
├── .htaccess                  Reglas de URL amigable
├── Dockerfile                 Imagen del servidor web
├── docker-compose.yml         Orquestación de servicios
├── .env.example               Plantilla de configuración
│
├── config/
│   ├── conexion.php           Instancia de PDO
│   └── php/99-opcache.ini     Ajustes de caché de opcode
│
├── includes/                  Capa de soporte transversal
│   ├── Db.php                 Singleton PDO con reconexión automática
│   ├── verificar_sesion.php   Guardia de sesión y requerirRol()
│   ├── permisos.php           Matriz de permisos por rol
│   ├── rol_panel.php          Redirección según rol
│   ├── validador.php          API unificada de validación
│   ├── funciones.php          Utilidades (CSRF, escape, formato)
│   ├── lista_helper.php       Helpers de listados
│   ├── carreras_globales.php  Catálogo canónico de carreras
│   └── cargar_env.php         Carga de variables de entorno
│
├── controllers/               71 controladores (uno por acción)
│   ├── login_procesar.php     Autenticación
│   ├── dashboard.php          Panel del administrador
│   ├── tutorias_*.php         Flujo de tutorías
│   ├── ofertas_*.php          Gestión de ofertas
│   ├── mg_*.php               Módulo Modalidades de Grado
│   └── usuarios_*.php         Gestión de cuentas
│
├── models/                    25 modelos de acceso a datos
│   ├── DashboardModel.php     Indicadores del panel
│   ├── TutoriaModel.php       Tutorías y calendario
│   ├── OfertaModel.php        Ofertas de tutoría
│   ├── DeclaracionModel.php   Declaraciones de modalidad
│   └── *MgModel.php           Modelos del módulo MG
│
├── views/                     Plantillas PHP con Vue embebido
│   ├── layouts/               Cabecera y pie comunes
│   ├── login/ landing/ dashboard/ perfil/
│   ├── admin/                 Catálogos y configuración
│   ├── tutor/ estudiante/     Paneles por rol
│   ├── mg/                    Vistas del módulo MG
│   └── error/403.php          Página de acceso denegado
│
├── database/
│   ├── init.sql               Esquema base + datos de ejemplo
│   ├── migrations/            Migraciones 001–003 y 009–017 (12 archivos)
│   └── README.md              Guía de versionado de esquema
│
├── scripts/
│   └── migrar.php             Runner idempotente de migraciones
│
├── assets/                    CSS, JS, imágenes y archivos subidos
├── docs/                      Documentación técnica y académica
└── scripts_bd_legacy/         Scripts de migración históricos
```

---

## 7. Puesta en marcha

### 7.1 Requisitos previos

- Docker Engine 24 o superior.
- Docker Compose v2.

### 7.2 Ejecución

```bash
# 1. Levantar la infraestructura
docker compose up -d

# 2. Aplicar las migraciones del esquema
docker exec -it <contenedor_web> php scripts/migrar.php

# 3. Acceder al sistema
#    Aplicación:   http://localhost:8010
#    phpMyAdmin:   http://localhost:8085
```

### 7.3 Configuración

Las variables de entorno se definen en `.env` (a partir de `.env.example`) o mediante
el orquestador:

| Variable | Descripción | Valor por defecto |
|---|---|---|
| `DB_HOST` | Servidor MySQL | `db` |
| `DB_NAME` | Nombre de la base | `tutorias_db` |
| `DB_USER` | Usuario de aplicación | `tutorias_user` |
| `DB_PASS` | Contraseña de aplicación | `12345` |
| `DB_PERSISTENT` | Habilita conexiones persistentes | `1` |
| `DB_PING` | Verificación de conexión viva | `1` |

### 7.4 Cuentas de demostración

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin` | `password` |
| Tutor | `tutor1` … `tutor10` | `password` |
| Estudiante | `estudiante1` … `estudiante50` | `password` |

---

## 8. Arquitectura

El sistema adopta el patrón **Modelo-Vista-Controlador** sobre una estructura
dirigida por el flujo de petición HTTP:

```
Navegador
    │  HTTP
    ▼
Apache (.htaccess)
    │
    ▼
Controller  ── valida sesión/permisos/CSRF ──►  Model  ── PDO ──►  MySQL 8
    │                                            │
    └──────────────► View (PHP + Vue) ◄──────────┘
```

- **Controller**: orquesta la petición, aplica la matriz de permisos, invoca al
  modelo y entrega los datos a la vista.
- **Model**: concentra las consultas SQL; no contiene lógica de presentación.
- **View**: plantilla PHP que renderiza el HTML y monta la capa reactiva de Vue.

Detalle en [`Arquitectura.md`](Arquitectura.md).

---

## 9. Modelo de datos

La base de datos se organiza en dos subconjuntos:

- **Núcleo institucional (15 tablas):** `usuarios`, `roles`, `estudiantes`,
  `tutores`, `materias`, `carreras`, `turnos`, `tutor_materia`,
  `disponibilidad_tutor`, `tutorias`, `evaluaciones_tutoria`, `ofertas_admin`,
  `oferta_respuesta`, `periodos`, `notificaciones`, `registro_accesos`.
- **Módulo Modalidades de Grado (18 tablas):** `modalidades_catalogo`,
  `declaraciones_modalidad`, `avales_declaracion`, `jurados_declaracion`,
  `actas_calificacion`, `acta_calificaciones_jurado`, `etapas_expediente`,
  `asignaciones_tutor`, `cohortes_mg`, `calendario_mg`, `plantillas_documento`,
  `documentos_generados`, `contadores_documento`, `importaciones_mg`,
  `importaciones_mg_detalle`, `parametros_mg`.

Diagrama entidad-relación en [`MER.md`](MER.md).

---

## 10. Seguridad

La aplicación implementa defensa en profundidad sobre cinco ejes:

| Eje | Mecanismo |
|---|---|
| Autenticación | `password_verify()` con hash **bcrypt** (`password_hash`). |
| Autorización | Matriz de permisos por rol (`permisos.php`) + `requerirRol()`. |
| Inyección SQL | Consultas preparadas PDO en el 100% de las entradas. |
| XSS | Escape de salida con `htmlspecialchars()` (helper `e()`). |
| CSRF | Token de sesión `bin2hex(random_bytes(32))` verificado con `hash_equals()`. |
|Fuerza bruta | Límite de 10 intentos fallidos por IP cada 15 minutos. |
| Fijación de sesión | `session_regenerate_id(true)` tras autenticación. |
| Auditoría | Tabla `registro_accesos` (usuario, IP, fecha, resultado). |
| Validación | API `validarFormulario()` con reglas por tipo de dato. |

Detalle en [`Seguridad.md`](Seguridad.md).

---

## 11. Control de versiones del esquema

El esquema evoluciona mediante migraciones numeradas en `database/migrations/`,
aplicadas de forma **idempotente** por `scripts/migrar.php`, que registra cada
versión aplicada en la tabla `schema_migrations`.

| Versión | Contenido |
|---|---|
| 001 | Esquema base institucional |
| 002 | Notificaciones |
| 003 | Periodos académicos |
| 009–016 | Módulo Modalidades de Grado (roles, declaraciones, avales, actas, expedientes, cohortes, documentos) |
| 017 | Nivel académico en tutorías y ofertas |

---

## 12. Documentación

| Documento | Contenido |
|---|---|
| [`README.md`](README.md) | Este documento: visión general y puesta en marcha. |
| [`Manual_Usuario.md`](Manual_Usuario.md) | Guía de uso por rol. |
| [`Casos_De_Uso.md`](Casos_De_Uso.md) | Especificación de casos de uso. |
| [`Requerimientos.md`](Requerimientos.md) | Requerimientos funcionales y no funcionales. |
| [`Arquitectura.md`](Arquitectura.md) | Arquitectura del sistema y diagramas. |
| [`MER.md`](MER.md) | Modelo entidad-relación. |
| [`Seguridad.md`](Seguridad.md) | Análisis de seguridad implementado. |
| [`Tecnologias.md`](Tecnologias.md) | Justificación technological. |
| [`Tabla_Pruebas.md`](Tabla_Pruebas.md) | Plan y resultados de pruebas (26 casos). |
| [`Limitaciones.md`](Limitaciones.md) | Limitaciones técnicas y funcionales. |
| [`Trabajo_Futuro.md`](Trabajo_Futuro.md) | Líneas de mejora futura. |
| [`Conclusiones.md`](Conclusiones.md) | Conclusiones del proyecto. |
| [`Presentacion_Defensa.md`](Presentacion_Defensa.md) | Presentación completa para la defensa. |

---

## 13. Autoría

Proyecto desarrollado como coursework del área de **Tecnologías Web**, empleando
PHP, MySQL, PDO, Apache, Docker y Vue.js bajo un esquema MVC sin framework.

---

## 14. Licencia

Material académico de uso educativo. Todos los derechos reservados por la
institución universitaria.
