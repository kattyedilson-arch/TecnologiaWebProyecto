# Requerimientos del Sistema
## Sistema de Tutorías y Modalidades de Grado

**Especificación de Requisitos de Software**
**Versión:** 1.0

---

## 1. Introducción

Este documento especifica los requisitos funcionales y no funcionales del **Sistema
de Tutorías y Modalidades de Grado**. La especificación responde a las necesidades
identificadas en la fase de análisis y constituye la base de la verificación del
sistema.

La clasificación se realiza según su naturaleza:

- **Requisitos funcionales (RF):** describen las funciones que el sistema debe
  ofrecer.
- **Requisitos no funcionales (RNF):** describen las qualities atributos que debe
  satisfacer el sistema (rendimiento, seguridad, usabilidad, mantenibilidad,
  portabilidad y confiabilidad).

---

## 2. Marco contextual del sistema

### 2.1 Actores del sistema

| Actor | Rol | Ámbito de actuación |
|---|---|---|
| Administrador | `administrador` | Gestión total del sistema y soporte de todos los módulos. |
| Tutor | `tutor` | Impartir tutorías, gestionar disponibilidad y disponibilidad de agendas. |
| Estudiante | `estudiante` | Solicitar y evaluar tutorías; declarar modalidades de grado. |
| Coordinador MG | `coordinador_mg` | Coordinar el proceso de modalidades de grado. |
| Auxiliar MG | `auxiliar_mg` | Ejecutar la operación diaria del proceso de grado. |

### 2.2 Módulos funcionales

| Módulo | Descripción |
|---|---|
| **Identificación y acceso** | Autenticación, sesión, notificaciones y perfil. |
| **Núcleo institucional** | Usuarios, roles, carreras, materias, turnos. |
| **Tutorías** | Ofertas, disponibilidad, solicitudes, estados, evaluaciones. |
| **Modalidades de Grado** | Modalidades, declaraciones, avales, expedientes, tribunal, actas, documentos. |
| **Gestión y reportes** | Panel de indicadores, reportes del módulo MG. |

---

## 3. Requerimientos funcionales

### 3.1 Módulo de identificación y acceso

| ID | Requerimiento | Actor |
|---|---|---|
| **RF-001** | El sistema debe permitir el inicio de sesión mediante nombre de usuario o correo electrónico. | Todos |
| **RF-002** | El sistema debe validar la contraseña mediante comparación segura con hash. | Todos |
| **RF-003** | El sistema debe impedir el acceso a cuentas inactivas. | Todos |
| **RF-004** | El sistema debe cerrar la sesión de forma segura. | Todos |
| **RF-005** | El sistema debe redirigir al usuario a su panel correspondiente según su rol. | Todos |
| **RF-006** | El sistema debe registrar cada intento de acceso (exitoso o fallido) con usuario, IP y fecha. | Sistema |
| **RF-007** | El sistema debe notificar al usuario los eventos relevantes de sus procesos. | Todos |
| **RF-008** | El sistema debe permitir consultar y marcar notificaciones como leídas. | Todos |
| **RF-009** | El sistema debe permitir editar los datos personales del perfil. | Todos |
| **RF-010** | El sistema debe permitir cargar una fotografía de perfil. | Todos |
| **RF-011** | El sistema debe bloquear temporalmente el acceso tras superar el límite de intentos fallidos. | Sistema |

### 3.2 Módulo de núcleo institucional

| ID | Requerimiento | Actor |
|---|---|---|
| **RF-012** | El sistema debe permitir crear, editar, desactivar y eliminar usuarios. | Administrador |
| **RF-013** | El sistema debe garantizar la unicidad del correo y del nombre de usuario. | Sistema |
| **RF-014** | El sistema debe permitir asignar un rol a cada usuario. | Administrador |
| **RF-015** | El sistema debe permitir crear, editar y eliminar carreras. | Administrador |
| **RF-016** | El sistema debe permitir crear, editar y eliminar materias. | Administrador |
| **RF-017** | El sistema debe permitir registrar y consultar los turnos predefinidos. | Administrador |
| **RF-018** | El sistema debe permitir registrar tutores y asociarlos con usuarios y materias. | Administrador |
| **RF-019** | El sistema debe permitir registrar estudiantes y asociarlos con su carrera y semestre. | Administrador |
| **RF-020** | El sistema debe validar el nombre de carrera contra el catálogo institucional. | Sistema |

### 3.3 Módulo de tutorías

| ID | Requerimiento | Actor |
|---|---|---|
| **RF-021** | El administrador debe poder crear ofertas de tutoría por materia, nivel académico y turno. | Administrador |
| **RF-022** | El sistema debe permitir definir el nivel académico, incluyendo valores personalizados. | Administrador |
| **RF-023** | El sistema debe exigir lugar para la modalidad presencial y enlace para la modalidad virtual. | Sistema |
| **RF-024** | El tutor debe poder consultar las ofertas disponibles de sus materias. | Tutor |
| **RF-025** | El tutor debe poder aceptar o rechazar una oferta. | Tutor |
| **RF-026** | El tutor debe poder configurar su disponibilidad por materia y turno. | Tutor |
| **RF-027** | El sistema debe asignar automáticamente un tutor a la oferta aceptada. | Sistema |
| **RF-028** | El estudiante debe poder consultar los horarios disponibles de las materias de su carrera. | Estudiante |
| **RF-029** | El estudiante debe poder solicitar una tutoría sobre un horario publicado. | Estudiante |
| **RF-030** | El sistema debe asignar automáticamente la fecha de la sesión según disponibilidad. | Sistema |
| **RF-031** | El sistema debe heredar tutor, turno, modalidad, nivel y lugar desde la oferta. | Sistema |
| **RF-032** | El sistema debe impedir solicitudes duplicadas del mismo estudiante para la misma materia. | Sistema |
| **RF-033** | El sistema debe controlar el ciclo de estados de la tutoría. | Sistema |
| **RF-034** | El tutor debe poder gestionar el estado de sus propias tutorías. | Tutor |
| **RF-035** | El estudiante debe poder cancelar sus tutorías mientras no se hayan realizado. | Estudiante |
| **RF-036** | El administrador debe poder gestionar cualquier transición de estado. | Administrador |
| **RF-037** | El sistema debe notificar a los implicados ante cada cambio de estado. | Sistema |
| **RF-038** | El estudiante debe poder evaluar una tutoría realizada con una calificación de 1 a 5. | Estudiante |
| **RF-039** | El sistema debe permitir una sola evaluación por tutoría. | Sistema |
| **RF-040** | El tutor debe poder consultar el listado de sus estudiantes. | Tutor |
| **RF-041** | El administrador debe poder filtrar y consultar el histórico de tutorías. | Administrador |

### 3.4 Módulo de tutorías – indicadores

| ID | Requerimiento | Actor |
|---|---|---|
| **RF-042** | El sistema debe presentar un panel de indicadores con totales de usuarios, estudiantes, tutores, materias, carreras y tutorías. | Administrador |
| **RF-043** | El sistema debe mostrar la distribución de tutorías por estado. | Administrador |
| **RF-044** | El sistema debe mostrar el promedio de las evaluaciones de las tutorías. | Administrador |
| **RF-045** | El sistema debe mostrar el ranking de los tutores mejor evaluados. | Administrador |
| **RF-046** | El sistema debe mostrar las materias más solicitadas. | Administrador |
| **RF-047** | El sistema debe mostrar el desglose de tutorías por nivel académico. | Administrador |
| **RF-048** | El sistema debe mostrar el resumen de ofertas abiertas, cerradas y aceptadas. | Administrador |
| **RF-049** | El sistema debe mostrar la disponibilidad de tutores por turno. | Administrador |
| **RF-050** | El panel debe permitir filtrar la actividad reciente por texto, estado y nivel. | Administrador |

### 3.5 Módulo de Modalidades de Grado – catálogo y configuración

| ID | Requerimiento | Actor |
|---|---|---|
| **RF-051** | El sistema debe permitir registrar modalidades de grado con código, nombre, tipo y requisitos. | Coordinador MG |
| **RF-052** | El sistema debe permitir publicar y despublicar modalidades. | Coordinador MG |
| **RF-053** | El sistema debe permitir indicar si una modalidad requiere tutor. | Coordinador MG |
| **RF-054** | El sistema debe permitir crear y gestionar periodos académicos. | Coordinador MG |
| **RF-055** | El sistema debe permitir crear y gestionar cohortes de estudiantes. | Coordinador MG |
| **RF-056** | El sistema debe permitir definir el calendario de hitos del proceso. | Coordinador MG |
| **RF-057** | El sistema debe permitir configurar los parámetros institucionales del módulo. | Coordinador MG |
| **RF-058** | Los parámetros configurables deben incluir nota mínima, escala máxima y plazos de citación. | Sistema |

### 3.6 Módulo de Modalidades de Grado – operación

| ID | Requerimiento | Actor |
|---|---|---|
| **RF-059** | El estudiante debe poder declarar una modalidad de grado publicada. | Estudiante |
| **RF-060** | El sistema debe controlar el flujo de estados de la declaración. | Sistema |
| **RF-061** | El sistema debe permitir registrar y dar seguimiento a los avales de una declaración. | Auxiliar MG |
| **RF-062** | El sistema debe permitir adjuntar documentos a los avales. | Auxiliar MG |
| **RF-063** | El sistema debe permitir observar un aval con la notificación al estudiante. | Auxiliar MG |
| **RF-064** | El sistema debe permitir la revisión de declaraciones por el equipo MG. | Auxiliar MG, Coordinador |
| **RF-065** | El sistema debe permitir aprobar o rechazar declaraciones con observación. | Coordinador MG |
| **RF-066** | El sistema debe notificar al estudiante el resultado de su declaración. | Sistema |
| **RF-067** | El sistema debe permitir la importación masiva de expedientes desde archivo CSV. | Auxiliar MG |
| **RF-068** | El sistema debe clasificar el resultado de cada fila importada. | Sistema |
| **RF-069** | El sistema debe registrar el detalle de la importación para su auditoría. | Sistema |
| **RF-070** | El sistema debe permitir asignar tutores de modalidad a las declaraciones. | Coordinador MG |
| **RF-071** | El sistema debe conservar el historial de asignaciones sin eliminar registros. | Sistema |
| **RF-072** | El sistema debe permitir registrar las etapas del expediente. | Auxiliar MG, Coordinador |
| **RF-073** | El sistema debe permitir asignar el tribunal (jurados) de la defensa. | Auxiliar MG |
| **RF-074** | El sistema debe permitir registrar las notas de cada jurado. | Auxiliar MG |
| **RF-075** | El sistema debe calcular la nota final y determinar el resultado según la nota mínima aprobada. | Sistema |
| **RF-076** | El sistema debe permitir firmar el acta y registrar al presidente del tribunal. | Auxiliar MG |
| **RF-077** | El sistema debe permitir generar documentos oficiales con número correlativo. | Auxiliar MG |
| **RF-078** | El sistema debe conservar una copia del contenido de cada documento generado. | Sistema |
| **RF-079** | El sistema debe permitir versionar las plantillas de documentos. | Coordinador MG |
| **RF-080** | El sistema debe permitir consultar y exportar reportes del módulo MG. | Coordinador MG |

### 3.7 Gestión de roles del módulo MG

| ID | Requerimiento | Actor |
|---|---|---|
| **RF-081** | El sistema debe permitir asignar los roles de coordinador MG y auxiliar MG. | Administrador |
| **RF-082** | El sistema debe aplicar una matriz de permisos por rol a todas las operaciones del módulo. | Sistema |
| **RF-083** | El sistema debe denegar el acceso a las funciones no autorizadas para el rol del usuario. | Sistema |

---

## 4. Requerimientos no funcionales

### 4.1 Rendimiento

| ID | Requerimiento | Criterio de aceptación |
|---|---|---|
| **RNF-001** | El tiempo de respuesta de las consultas de lectura del dashboard debe ser inferior a 2 segundos con 10 000 registros. | Medición sobre el conjunto de datos de prueba. |
| **RNF-002** | El tiempo de carga de las páginas debe ser inferior a 3 segundos en conexión local. | Verificado mediante solicitud HTTP. |
| **RNF-003** | Las consultas deben usar índices adecuados en los campos de búsqueda frecuente. | Índices definidos en fecha, estado, tutor y estudiante. |
| **RNF-004** | El sistema debe emplear conexiones persistentes al servidor de base de datos. | Habilitadas mediante `DB_PERSISTENT`. |
| **RNF-005** | La importación masiva de expedientes debe procesar el padrón sin bloquear la interfaz. | Procesamiento por filas independientes. |

### 4.2 Seguridad

| ID | Requerimiento | Criterio de aceptación |
|---|---|---|
| **RNF-006** | Las contraseñas deben almacenarse únicamente como hash con algoritmo bcrypt. | `password_hash()` con `PASSWORD_DEFAULT`. |
| **RNF-007** | Todas las consultas SQL deben parametrizarse para evitar la inyección de código. | 100% de uso de consultas preparadas PDO. |
| **RNF-008** | Toda salida dinámica debe escaparse para evitar la inyección de scripts (XSS). | Uso de `htmlspecialchars()` en las vistas. |
| **RNF-009** | Los formularios de acción deben incluir un token anti-CSRF. | Verificación con `hash_equals()`. |
| **RNF-010** | El identificador de sesión debe regenerarse tras la autenticación. | `session_regenerate_id(true)`. |
| **RNF-011** | El acceso debe limitarse a usuarios autenticados y autorizados por rol. | Guardia de sesión y matriz de permisos. |
| **RNF-012** | Los intentos de autenticación deben quedar registrados para auditoría. | Tabla `registro_accesos`. |
| **RNF-013** | El sistema debe aplicar un límite de intentos fallidos por origen de red. | 10 intentos por IP cada 15 minutos. |
| **RNF-014** | Los mensajes de autenticación no deben revelar la existencia de la cuenta. | Mensaje genérico de credenciales. |
| **RNF-015** | Los archivos subidos deben almacenarse en el servidor con nombres controlados. | Registro con nombre original y ruta generada. |

### 4.3 Usabilidad

| ID | Requerimiento | Criterio de aceptación |
|---|---|---|
| **RNF-016** | La interfaz debe ser responsiva y adaptarse a dispositivos de distintos tamaños. | Diseño adaptable con CSS. |
| **RNF-017** | El sistema debe comunicar el resultado de cada operación mediante mensajes claros. | Mensajes de éxito, error y advertencia. |
| **RNF-018** | Los formularios deben indicar claramente los errores de validación. | Mensajes por campo y validación en línea. |
| **RNF-019** | La navegación debe ser coherente y consistente entre módulos. | Menú lateral homogéneo. |
| **RNF-020** | La interfaz debe ofrecer mensajes de confirmación antes de acciones destructivas. | Confirmación en eliminaciones. |
| **RNF-021** | El sistema debe preservar la identidad visual institucional. | Uso de la paleta y logotipo institucional. |

### 4.4 Mantenibilidad

| ID | Requerimiento | Criterio de aceptación |
|---|---|---|
| **RNF-022** | El código debe seguir una estructura MVC separando responsabilidades. | Separación de carpetas y capas. |
| **RNF-023** | Los modelos deben ser independientes de la capa de presentación. | Modelos sin lógica de vista. |
| **RNF-024** | La lógica común debe estar centralizada en funciones reutilizables. | Archivo de funciones auxiliares. |
| **RNF-025** | La validación debe ofrecer una interfaz unificada y declarativa. | API de validación por esquema. |
| **RNF-026** | El esquema de la base de datos debe evolucionar mediante migraciones versionadas. | Archivos numerados y registro de versiones. |
| **RNF-027** | Las migraciones deben ser idempotentes y re-ejecutables sin efectos adversos. | Verificación mediante re-ejecución. |
| **RNF-028** | La configuración debe externalizarse del código fuente. | Variables de entorno y archivo `.env`. |
| **RNF-029** | El código fuente debe documentarse mediante comentarios en español. | Documentación de clases y métodos. |

### 4.5 Portabilidad y despliegue

| ID | Requerimiento | Criterio de aceptación |
|---|---|---|
| **RNF-030** | El sistema debe ejecutarse de forma reproducible mediante contenedores. | Definición en Docker Compose. |
| **RNF-031** | El despliegue no debe requerir configuración manual de la base de datos. | Script de inicialización automático. |
| **RNF-032** | El sistema debe ser independiente del sistema operativo del servidor. | Contenedores basados en Linux. |
| **RNF-033** | Los datos deben protegerse con el estándar de codificación UTF-8. | Conjuntos `utf8mb4` en base de datos y conexiones. |

### 4.6 Confiabilidad e integridad

| ID | Requerimiento | Criterio de aceptación |
|---|---|---|
| **RNF-034** | La base de datos debe garantizar la integridad referencial entre entidades. | Claves foráneas en todas las relaciones. |
| **RNF-035** | La base de datos debe impedir duplicados en claves compuestas relevantes. | Restricciones UNIQUE. |
| **RNF-036** | La base de datos debe aplicar restricciones de dominio mediante tipos enumerados. | Campos ENUM para estados controlados. |
| **RNF-037** | El sistema debe validar los rangos de los datos numéricos. | Restricciones y validación de entrada. |
| **RNF-038** | El sistema debe recuperar la conexión ante fallo de la sesión de base de datos. | Reintento automático en la capa de conexión. |
| **RNF-039** | Los documentos generados deben conservarse como copia histórica inalterable. | Almacenamiento del contenido generado. |
| **RNF-040** | El historial de cambios significativos debe conservarse para auditoría. | Tablas de historial de asignación y etapas. |

### 4.7 Compatibilidad

| ID | Requerimiento | Criterio de aceptación |
|---|---|---|
| **RNF-041** | El sistema debe funcionar en navegadores modernos. | Chrome, Firefox, Edge y Safari recientes. |
| **RNF-042** | La capa interactiva debe funcionar sin compilación de código en el cliente. | Vue.js distribuido por CDN. |
| **RNF-043** | El sistema debe ser accesible desde dispositivos móviles. | Diseño responsivo. |

---

## 5. Matriz de trazabilidad

| Módulo | Requisitos funcionales | Casos de uso | Requisitos no funcionales |
|---|---|---|---|
| Identificación y acceso | RF-001 – RF-011 | UC-01, UC-02, UC-03, UC-04 | RNF-006 – RNF-015 |
| Núcleo institucional | RF-012 – RF-020 | UC-40, UC-41 | RNF-034 – RNF-037 |
| Tutorías | RF-021 – RF-041 | UC-10 – UC-17 | RNF-001 – RNF-005, RNF-022 |
| Indicadores | RF-042 – RF-050 | UC-16 | RNF-001, RNF-003 |
| Modalidades de Grado | RF-051 – RF-080 | UC-20 – UC-32 | RNF-005, RNF-016, RNF-022 |
| Roles del módulo MG | RF-081 – RF-083 | Todos los del MG | RNF-011 |
| Transversales | — | — | RNF-016 – RNF-043 |

---

## 6. Reglas de negocio asociadas

Las reglas de negocio que condicionan el cumplimiento de los requisitos anteriores
se detallan en el documento [`Casos_De_Uso.md`](Casos_De_Uso.md), con códigos RN-01
a RN-79.

---

## 7. Restricciones del proyecto

| ID | Restricción |
|---|---|
| **RS-001** | El proyecto debe desarrollar-se sin el uso de frameworks PHP (Laravel, Symfony, CodeIgniter). |
| **RS-002** | El acceso a la base de datos debe realizarse mediante PDO. |
| **RS-003** | El proyecto debe ejecutarse sobre Docker. |
| **RS-004** | La interacción del cliente debe resolverse con Vue.js sin proceso de compilación. |
| **RS-005** | La interfaz debe permanecer en idioma español. |
| **RS-006** | El sistema debe operar en un entorno institucional con datos de estudiantes sensibles. |

---

## 8. Criterios de aceptación del sistema

El sistema se considera aceptado cuando:

1. Los 83 requisitos funcionales están implementados y verificados.
2. Los 43 requisitos no funcionales se cumplen según sus criterios de aceptación.
3. Los casos de prueba definidos en [`Tabla_Pruebas.md`](Tabla_Pruebas.md) se
   ejecutan con resultado satisfactorio.
4. No se registran errores fatales ni vulnerabilidades de seguridad conocidas.
5. La documentación técnica y de usuario se encuentra completa y actualizada.

---

*Fin del documento de Requerimientos*
