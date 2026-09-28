# Conclusiones
## Sistema de Tutorías y Modalidades de Grado

**Documento de Conclusiones**
**Versión:** 1.0

---

## 1. Introducción

Este documento presenta las conclusiones del desarrollo del **Sistema de Tutorías y
Modalidades de Grado**. Las conclusiones recogen los resultados obtenidos tras la
elaboración del sistema y su documentación técnica, y valoran el cumplimiento de los
objetivos propuestos en la fase de planteamiento del proyecto.

El análisis se organiza alrededor de cinco ejes: el cumplimiento de los objetivos, el
aprendizaje técnico, la aplicación de la arquitectura, la garantía de la seguridad y
las proyecciones del sistema.

---

## 2. Objetivos alcanzados

| # | Objetivo propuesto | Resultado obtenido |
|---|---|---|
| 1 | Gestionar el proceso de tutorías de extremo a extremo. | **Alcanzado** — ofertas, disponibilidad, solicitud, seguimiento, estados y evaluación. |
| 2 | Gestionar las modalidades de grado. | **Alcanzado** — catálogo, periodos, declaraciones, avales, expedientes, tribunal, actas y documentos. |
| 3 | Implementar un sistema con roles y permisos diferenciados. | **Alcanzado** — cinco roles con matriz de permisos verificada. |
| 4 | Garantizar la seguridad de la información. | **Alcanzado** — controles de autenticación, autorización, integridad y auditoría. |
| 5 | Entregar un sistema desplegable de forma reproducible. | **Alcanzado** — entorno completo con Docker Compose y variables de entorno. |
| 6 | Producir documentación técnica y de usuario. | **Alcanzado** — doce documentos que cubren el ciclo de vida del software. |

---

## 3. Conclusiones sobre el cumplimiento funcional

### 3.1 Módulo de tutorías

El módulo de tutorías se implementó en su totalidad, cubriendo el ciclo de vida
completo de una sesión: desde la publicación de la oferta por parte del
administrador, pasando por la aceptación del tutor y la declaración de su
disponibilidad, hasta la solicitud del estudiante y el seguimiento de los estados
hasta su realización y evaluación.

Una decisión de diseño especialmente útil fue la **asignación automática de la fecha**
en el momento de la solicitud. Esta mecánica Evita la coordinación manual entre las
partes y reduce las solicitudes de reprogramación, al tiempo que garantiza la
ausencia de conflictos en la agenda del tutor.

El control de estados mediante una máquina explícita
(`pendiente → confirmada → en_proceso → realizada`, con `cancelada` como salida
alternativa) aporta una garantía estructural: el sistema no admite
transiciones inválidas, lo que reduce la posibilidad de inconsistencias en la
información registrada.

### 3.2 Módulo de modalidades de grado

El módulo de modalidades de grado representa el componente de mayor complejidad del
sistema, pues integra la declaración del estudiante con la operación administrativa
del equipo de coordenação, los expedientes, la asignación de tutores, la conformación
del tribunal y el registro del acta de defensa.

La implementación de la **trazabilidad del proceso** resulta el aspecto más relevante
de este módulo. El sistema conserva el historial de las asignaciones de tutores
—marcando las anteriores como reemplazadas en lugar de eliminarlas—, registra cada
etapa del expediente con su fecha, resultado y responsable, y sella el acta con la
fecha de firma. Esta decisión garantiza que la información histórica del proceso
permanezca disponible para consultas posteriores.

La gestión de avales con sus estados (`pendiente`, `entregado`, `observado`) y la
regla que impide aprobar una declaración mientras existan avales pendientes
garantizan la completitud documental del proceso antes de su aprobación.

### 3.3 Identificación y control de acceso

La implementación de cinco roles —administrador, tutor, estudiante, coordinador de
modalidades de grado y auxiliar de modalidades de grado— permitió modelar el sistema
según la estructura real de la institución. La matriz de permisos centralizada en un
único punto del sistema facilita su auditoría y su mantenimiento.

La verificación de la propiedad del recurso complementa el control por rol: el tutor
solo accede a las tutorías que imparte y el estudiante únicamente a las suyas. Esta
doble verificación —rol y pertenencia— es indispensable en un sistema donde los
usuarios comparten información institucional.

---

## 4. Conclusiones técnicas

### 4.1 Arquitectura y patrones aplicados

La adopción del patrón **Modelo-Vista-Controlador** con un front controller único
permitió separar de forma clara las responsabilidades del sistema: los controladores
coordinan el flujo, los modelos encapsulan el acceso a datos y las vistas se limitan a
presentar la información.

El detalle completo de la arquitectura puede consultarse en
[`Arquitectura.md`](Arquitectura.md).

La restricción de no emplear frameworks resultó ser el mayor desafío técnico del
proyecto, y simultáneamente la fuente de mayor aprendizaje. Implementar manualmente
el enrutamiento, la gestión de sesiones, la capa de conexión a datos y los mecanismos
de protección permitió comprender el funcionamiento interno de estas capas, un
conocimiento que no se obtiene al utilizar abstracciones prefabricadas.

### 4.2 Base de datos

El modelo de datos quedó organizado en cuatro dominios claramente delimitados:
identidad, estructura institucional, tutorías y modalidades de grado. El uso de
campos `ENUM` para los estados controlados, restricciones `UNIQUE` en las
combinaciones de negocio y claves foráneas con comportamiento `RESTRICT` permitió que
la integridad del modelo descansara en el propio motor de base de datos, y no
únicamente en la lógica de la aplicación.

El uso de migraciones versionadas garantiza que el esquema pueda reconstruirse de
forma reproducible y auditable, evitando la dependencia del estado de una base de
datos preexistente.

### 4.3 Interfaz de usuario

La interfaz se desarrolló con una hoja de estilos propia, coherente con la identidad
institucional, y con Vue.js como capa de interactividad sin proceso de compilación.
Esta decisión redujo la complejidad del despliegue y permitió que el sistema
funcionara inmediatamente después de la instalación de los contenedores.

La validación se aplicó en dos niveles: en el navegador para mejorar la
experiencia del usuario y en el servidor como mecanismo de seguridad real. El
segundo nivel es el único que garantiza la integridad de la información, tal como se
detalla en [`Seguridad.md`](Seguridad.md).

---

## 5. Conclusiones sobre la seguridad

La seguridad fue considerada un requisito transversal y no un añadido final. Las
medidas implementadas cubren las principales categorías de riesgo:

| Categoría | Medidas aplicadas |
|---|---|
| **Autenticación** | bcrypt, `password_verify()`, mensajes genéricos, limitación de intentos y auditoría. |
| **Autorización** | Matriz de permisos por rol, verificación de la propiedad del recurso y denegación por defecto. |
| **Integridad** | Consultas preparadas al 100%, validación declarativa y control de transiciones de estado. |
| **Confidencialidad** | Escape de toda salida dinámica y cookies de sesión protegidas. |
| **Trazabilidad** | Registro de accesos e historial inmutable de los procesos de grado. |

El detalle de los 24 controles implementados puede consultarse en
[`Seguridad.md`](Seguridad.md).

La conclusión principal en este ámbito es que la seguridad no depende de la
disciplina del programador sino de la estructura del sistema: al utilizar consultas
preparadas de manera sistemática y una capa centralizada de autorización, se reduce
considerablemente la posibilidad de errores.

---

## 6. Conclusiones sobre la documentación

La elaboración de los doce documentos que acompañan al sistema permitió verificar su
consistencia interna y detectar observaciones sobre el modelo de datos, los flujos de
trabajo y los roles. La documentación constituye un componente de entrega de primer
orden, comparable al propio código, porque es la que permite comprender el sistema y
mantenerlo en el futuro.

| Documento | Aporte principal |
|---|---|
| `README.md` | Panorama general y puesta en marcha. |
| `Manual_Usuario.md` | Procedimientos para los cinco roles. |
| `Casos_De_Uso.md` | Especificación funcional con 79 reglas de negocio. |
| `Requerimientos.md` | 83 requisitos funcionales y 43 no funcionales. |
| `Arquitectura.md` | Capas, patrones y decisiones de diseño. |
| `MER.md` | Modelo de datos y máquinas de estado. |
| `Seguridad.md` | Análisis de amenazas y controles implementados. |
| `Tecnologias.md` | Stack tecnológico y su justificación. |
| `Tabla_Pruebas.md` | 120 casos de prueba con trazabilidad a requisitos. |
| `Limitaciones.md` | Alcance real y restricciones del sistema. |
| `Trabajo_Futuro.md` | Líneas de evolución propuestas. |
| `Conclusiones.md` | Este documento. |

---

## 7. Conclusiones sobre el aprendizaje

| Área | Aprendizaje |
|---|---|
| **Desarrollo web en PHP** | Comprensión del ciclo de vida de una petición HTTP y del papel de cada capa. |
| **Patrones de diseño** | Aplicación efectiva de MVC, front controller y repositorio. |
| **Seguridad web** | Identificación y mitigación de los ataques más comunes en aplicaciones PHP. |
| **Modelado de datos** | Construcción de un modelo relacional coherente con un dominio complejo. |
| **Control de versiones** | Gestión del esquema mediante migraciones idempotentes. |
| **Despliegue** | Uso de contenedores para garantizar la reproducibilidad del entorno. |
| **Documentación técnica** | Elaboración de documentos con propósito y audiencia diferenciados. |

---

## 8. Conclusiones sobre la aplicabilidad del sistema

El sistema es aplicable en el contexto académico para el que fue diseñado:
instituciones de educación superior con programas de tutorías y modalidades de
grado. La estructura de roles admite adaptaciones organizacionales menores, y el
modelo de datos puede admitir nuevas modalidades de grado sin modificar el
esquema central, dado que el catálogo de modalidades es paramétrico.

Las mejoras necesarias para un entorno de producción de mayor escala se encuentran
detalladas en [`Limitaciones.md`](Limitaciones.md) y [`Trabajo_Futuro.md`](Trabajo_Futuro.md).

---

## 9. Conclusión general

El **Sistema de Tutorías y Modalidades de Grado** se desarrolló completo y cumple los
objetivos propuestos en su planteamiento. El sistema integra dos dominios de gestión
—tutorías y modalidades de grado— bajo una arquitectura común, con un modelo de
seguridad sólido, un modelo de datos normalizado y una documentación exhaustiva que
acompaña cada aspecto de su funcionamiento.

La experiencia de desarrollo confirmó que el cumplimiento de una restricción —en este
caso, la prohibición de usar frameworks— puede convertirse en un limitante
significativo, pero también en la oportunidad de obtener un aprendizaje mucho más
profundo. El resultado es un sistema funcional, seguro, documentado y desplegable,
capaz de responder a las necesidades de los cinco roles que participan en los
procesos de tutoría y modalidades de grado.

---

*Fin del documento de Conclusiones*
