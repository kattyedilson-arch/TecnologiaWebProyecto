# Manual de Usuario
## Sistema de Tutorías y Modalidades de Grado

**Versión:** 1.0
**Perfil:** Todos los roles del sistema
**Plataforma:** Navegador web (escritorio o dispositivo con navegador moderno)

---

## 1. Introducción

Este manual describe el procedimiento operativo del **Sistema de Tutorías y
Modalidades de Grado**. Está dirigido a los cinco perfiles de usuario del sistema y
describe, paso a paso, las operaciones disponibles para cada uno.

El sistema opera bajo un modelo de **control de acceso por roles**, por lo que las
funciones disponibles dependen del perfil con el que se inicia sesión. El contenido se
organiza por rol, permitiendo que cada usuario consultes únicamente el apartado que
le corresponde.

---

## 2. Requisitos para el uso del sistema

### 2.1 Requisitos de hardware

- Computador o dispositivo con pantalla de 1024×768 píxeles o superior.
- Conexión a Internet o a la red institucional.

### 2.2 Requisitos de software

- Navegador web moderno: Google Chrome, Mozilla Firefox, Microsoft Edge o Safari
  (versiones recientes).
- JavaScript habilitado (requerido por la capa de interacción con Vue.js).

### 2.3 Requisitos de la cuenta

- Credenciales asignadas por el administrador del sistema.
- Cuenta en estado **activo** (una cuenta inactiva no puede iniciar sesión).

---

## 3. Acceso al sistema

### 3.1 Ingreso

1. Abra el navegador y acceda a la dirección del sistema (por ejemplo,
   `http://localhost:8010`).
2. Se mostrará la **portada institucional**. Pulse el botón de acceso.
3. En el formulario de ingreso, introduzca:
   - **Usuario o correo electrónico:** su nombre de usuario o su correo registrado.
   - **Contraseña:** su clave de acceso.
4. Pulse **Iniciar sesión**.

### 3.2 Mensajes de error en el ingreso

| Mensaje | Causa | Solución |
|---|---|---|
| *Usuario o contraseña incorrectos, o cuenta inactiva.* | Credenciales erróneas o cuenta desactivada. | Verifique los datos; si la cuenta está inactiva, solicite su reactivación al administrador. |
| *La solicitud expiró. Vuelve a intentar iniciar sesión.* | Token de seguridadinvalidado. | Recargue la página e intente nuevamente. |
| *Demasiados intentos fallidos. Espera 15 minutos…* | Se superó el límite de 10 intentos por IP. | Espere 15 minutos antes de reintentar. |

> **Nota de seguridad:** el sistema registra cada intento de acceso (exitoso o
> fallido) junto con la dirección IP y la fecha, para efectos de auditoría.

### 3.3 Cierre de sesión

Pulse el ícono de usuario en la barra superior y seleccione **Cerrar sesión**. La
sesión se destruye completamente en el servidor.

---

## 4. Interfaz general

La interfaz se organiza en los siguientes elementos:

| Elemento | Descripción |
|---|---|
| **Barra superior** | Logo institucional, nombre del sistema, notificaciones, menú de usuario. |
| **Menú lateral** | Opciones de navegación según el rol activo. |
| **Área de contenido** | Panel de la sección seleccionada. |
| **Notificaciones** | Ícono con contador de mensajes no leídos. |
| **Mensajes flash** | Avisos de éxito o error tras cada operación. |

Las pantallas están adaptadas a dispositivos móviles mediante diseño *responsive*.

---

## 5. Módulo de Tutorías

### 5.1 Administrador

#### 5.1.1 Panel de control (Dashboard)

Al iniciar sesión, el administrador accede al **Dashboard**, que presenta:

- **Tarjetas de indicadores:** total de usuarios, estudiantes, tutores, materias,
  carreras y tutorías; distribución por estado; promedio de evaluaciones.
- **Actividad reciente:** tabla con las últimas seis tutorías registradas
  (estudiante, materia, tutor, fecha, nivel académico y estado).
- **Ranking de tutores:** los cinco tutores mejor evaluados, con su promedio de
  calificación y número de sesiones.
- **Materias más solicitadas:** gráfica de barras con las cinco materias con más
  tutorías.
- **Desglose por nivel académico:** distribución de tutorías por nivel
  (pregrado, posgrado, invierno, verano o nivel personalizado).

El panel incluye **filtros de búsqueda** por texto, materia, estado y nivel.

#### 5.1.2 Gestión de usuarios

1. Acceda a **Usuarios → Listar**.
2. Para **crear** un usuario: pulse *Nuevo usuario* y complete nombre, apellido,
   correo, nombre de usuario, contraseña, rol y teléfono.
3. Para **editar**: pulse el ícono de edición en la fila correspondiente.
4. Para **desactivar**: use la acción de estado. La cuenta se conserva pero no puede
   iniciar sesión.
5. Para **eliminar**: pulse el ícono de papelera. El sistema solicita confirmación.

#### 5.1.3 Gestión de tutores

1. Acceda a **Tutores → Listar**.
2. Registre un tutor indicando su usuario asociado, especialidad y biografía.
3. Asigne **materias** que podrá impartir tutorías.
4. Configure su **disponibilidad**: seleccione materia y turno. El sistema valida
   que no haya duplicados.

#### 5.1.4 Gestión de estudiantes

1. Acceda a **Estudiantes → Listar**.
2. Registre o edite la ficha del estudiante: carrera, semestre y número de registro
   universitario.
3. El número de registro universitario debe ser único en el sistema.

#### 5.1.5 Gestión de materias y carreras

- **Materias:** alta, edición y eliminación. Cada materia puede asociarse a una
  carrera.
- **Carreras:** alta, edición y eliminación. El sistema valida el nombre contra un
  catálogo institucional de carreras y corrige errores tipográficos frecuentes.

#### 5.1.6 Creación de ofertas de tutoría

1. Acceda a **Ofertas → Crear**.
2. Seleccione la **materia**, el **nivel académico** y el **turno**.
   - El nivel académico admite los valores pregrado, posgrado, invierno, verano o
     **otra (personalizar)**.
3. Defina la **modalidad**: presencial o virtual.
4. Para modalidad presencial indique el **lugar**; para virtual, el **enlace**.
5. Guarde la oferta. Quedará en estado *abierta* hasta que un tutor la acepte.

#### 5.1.7 Gestión de tutorías

1. Acceda a **Tutorías → Listar**.
2. Filtre por estado, materia, tutor o rango de fechas.
3. **Cambie el estado** de una tutoría mediante los botones de acción:
   - Aceptar (`pendiente` → `confirmada`)
   - Iniciar (`confirmada` → `en_proceso`)
   - Finalizar (`en_proceso` → `realizada`)
   - Cancelar (desde `pendiente`, `confirmada` o `en_proceso`)

> Como administrador, puede realizar cualquier transición de estado, incluidas las
> que los demás roles no tienen permitidas.

### 5.2 Tutor

#### 5.2.1 Panel del tutor

Al iniciar sesión, el tutor accede a su **panel personal**, que muestra:

- Tutorías pendientes de aceptación.
- Tutorías confirmadas próximas.
- Tutorías en proceso.
- Historial de tutorías realizadas.
- Calificación promedio recibida.

#### 5.2.2 Gestión de ofertas

1. Acceda a **Ofertas** desde el menú lateral.
2. Consulte las ofertas disponibles para las materias que imparte.
3. Pulse **Aceptar** o **Rechazar** en cada oferta.
4. Las ofertas aceptadas quedan asignadas a usted y se vuelven visibles para que los
   estudiantes las soliciten.

#### 5.2.3 Gestión de disponibilidad

1. Acceda a **Mi disponibilidad**.
2. Seleccione la **materia** y el **turno** en que puede atender.
3. Guarde. El sistema asigna a usted un cupo por cada combinación materia-turno.
4. No es posible registrar dos veces la misma combinación.

#### 5.2.4 Ciclo de vida de la tutoría

Desde el panel, el tutor ejecuta la tutoría en cuatro pasos:

| Paso | Acción | Estado resultante |
|---|---|---|
| 1 | **Aceptar** la solicitud | `confirmada` |
| 2 | **Iniciar** la sesión | `en_proceso` |
| 3 | **Finalizar** al término | `realizada` |
| — | **Cancelar** (si no se realizó) | `cancelada` |

> El sistema **notifica automáticamente** al estudiante en cada cambio de estado.

#### 5.2.5 Mis estudiantes

Acceda a **Mis estudiantes** para consultar el listado de estudiantes que tienen
tutorías registradas con usted, junto con el detalle de cada sesión y su estado.

#### 5.2.6 Evaluación de la tutoría

Al finalizar una sesión, el estudiante puede evaluar la tutoría. El tutor consulta
el promedio de sus calificaciones en el panel.

### 5.3 Estudiante

#### 5.3.1 Panel del estudiante

Al iniciar sesión, el estudiante accede a su panel, que muestra:

- Tutorías en estado pendiente, confirmadas, en proceso y realizadas.
- Historial completo de sesiones.
- Estado de sus declaraciones de modalidad de grado (si aplica).
- Notificaciones del sistema.

#### 5.3.2 Solicitud de tutoría

1. Desde el panel, pulse **Solicitar tutoría**.
2. El sistema muestra únicamente las **materias de su carrera** que tienen
   **horarios publicados y asignados**.
3. Seleccione el horario (turno) disponible.
4. Agregue observaciones si desea (máximo 1000 caracteres).
5. Pulse **Solicitar**.

El sistema asigna automáticamente:

- La **fecha**: el primer día libre del turno seleccionado, sin conflictos de agenda
  del tutor ni del estudiante.
- El **tutor**: derivado de la oferta aceptada.
- La **modalidad**, el **lugar o enlace** y el **nivel académico**: heredados de la
  oferta.

> **Restricción:** no es posible registrar dos solicitudes activas para la misma
> materia. Para cambiar, cancele la solicitud anterior.

#### 5.3.3 Seguimiento y cancelación

1. Consulte el estado de sus solicitudes en el panel.
2. Cuando el tutor acepte, recibirá una notificación y la tutoría pasará a
   `confirmada`.
3. Puede **cancelar** una tutoría mientras esté en estado `pendiente` o
   `confirmada`.

#### 5.3.4 Evaluación de la tutoría

1. Acceda a **Evaluar tutoría** una vez la sesión figure como `realizada`.
2. Seleccione una calificación de **1 a 5 estrellas**.
3. Escriba un comentario opcional.
4. Envíe la evaluación. Solo se permite una evaluación por tutoría.

#### 5.3.5 Declaración de modalidad de grado

1. Acceda a **Mi declaración**.
2. Seleccione la modalidad de grado del catálogo publicado.
3. Complete el título del proyecto, la organización (si aplica) y el tutor
   facultativo (si aplica).
4. Envíe la declaración. Queda en estado *borrador* hasta que la envíe formalmente.
5. Realice el seguimiento del estado: *enviada → en revisión → aprobada/rechazada*.

#### 5.3.6 Notificaciones

El ícono de notificaciones en la barra superior indica los mensajes no leídos.
Acceda a **Notificaciones** para ver el detalle y marcar como leídas.

---

## 6. Módulo de Modalidades de Grado (MG)

### 6.1 Coordinador MG

El **coordinador** es el responsable académico del proceso. Sus funciones incluyen:

#### 6.1.1 Panel de coordinación

Al iniciar sesión accede al **Panel MG**, que presenta:

- Indicadores del proceso: declaraciones por estado, expedientes activos.
- Bandeja de declaraciones pendientes de revisión.
- Actas de defensa por firmar.
- Reportes y estadísticas del módulo.

#### 6.1.2 Gestión de modalidades

1. Acceda a **Modalidades**.
2. **Registrar modalidad:** código, nombre, descripción, tipo, requisitos.
3. Active el indicador **requiere tutor** si la modalidad exige acompañamiento
   (proyecto, tesis, trabajo dirigido).
4. **Publicar** la modalidad para que los estudiantes puedan declararla.
5. Editar o eliminar modalidades según corresponda.

#### 6.1.3 Gestión de periodos académicos

1. Acceda a **Periodos**.
2. Cree un periodo con nombre, fecha de inicio y fecha de fin.
3. El estado (*abierto* / *cerrado*) controla si el periodo admite nuevas
   declaraciones.
4. Solo puede existir un periodo abierto a la vez.

#### 6.1.4 Gestión de cohortes

1. Acceda a **Cohortes**.
2. Cree una cohorte con código, nombre, periodo asociado, fechas y estado.
3. Las cohortes permiten agrupar estudiantes que/licen la modalidad en un mismo
   periodo.

#### 6.1.5 Asignación de tutores de modalidad

1. Acceda a **Bandeja / Expedientes**.
2. Seleccione la declaración del estudiante.
3. Consulte la disponibilidad del tutor y asigne el acompañamiento.
4. El sistema registra el historial completo de asignaciones (vigente, finalizada,
   reemplazada), sin eliminar registros previos.

#### 6.1.6 Revisión y aprobación de declaraciones

1. Acceda a **Bandeja de declaraciones**.
2. Revise la información de la declaración y sus avales.
3. **Apruebe** o **rechace** indicando la observación correspondiente.
4. El estudiante recibe la notificación con el resultado.

#### 6.1.7 Parámetros del módulo

1. Acceda a **Parámetros MG**.
2. Configure los valores institutional: nota mínima de aprobación, escala máxima,
   días de antelación para citación, separación mínima entre defensas.
3. Los parámetroseditables pueden ajustarse sin intervencion del código.

#### 6.1.8 Reportes

Acceda a **Reportes MG** para consultar y exportar indicadores del proceso:
distribución de modalidades, estados, tiempos de tramitación y resultados de actas.

### 6.2 Auxiliar MG

El **auxiliar** ejecuta la operación diaria del proceso. Sus funciones son:

#### 6.2.1 Panel operativo

Accede al **Panel MG** con la vista operativa centrada en la bandeja de trabajo.

#### 6.2.2 Gestión de avales

1. Acceda a **Avales** de la declaración correspondiente.
2. Registre los avales requeridos por la modalidad.
3. Cambie su estado: *pendiente*, *entregado* u *observado*.
4. **Cargue el documento** del aval (archivo digital). El sistema almacena tanto la
   ruta del archivo como su nombre original.

#### 6.2.3 Revisión de declaraciones

1. Acceda a **Bandeja de declaraciones**.
2. Verifique que la información esté completa y que los avales estén entregados.
3. Eleve la declaración al coordinador para su aprobación.

#### 6.2.4 Importación masiva de expedientes

1. Acceda a **Importar**.
2. Seleccione el archivo CSV con el padrón de estudiantes.
3. El sistema procesa cada fila y clasifica el resultado:
   - `ok` — expediente creado correctamente.
   - `advertencia` — creado con observaciones.
   - `pendiente_cuenta` — el estudiante no tiene cuenta de usuario.
   - `omitida` — la fila se omitió por duplicidad.
   - `error` — la fila no pudo procesarse.
4. Consulte el **detalle fila por fila** con el mensaje asociado.
5. El sistema muestra un resumen: total de filas, filas procesadas, con error y
   expedientes creados.

#### 6.2.5 Expedientes y etapas

1. Acceda a **Expedientes**.
2. Consulte el expediente del estudiante: declaraciones, avales, etapas y
   documentos.
3. Registre el avance por **etapas**: previa, MG1, MG2, finalizado.
4. Indique el resultado de cada etapa: aprobado, reprobado, abandono, retirado.

#### 6.2.6 Tribunal y actas de defensa

1. Acceda a **Tribunal** para asignar los jurados de la declaración
   (presidente, titular, suplente).
2. Acceda a **Actas** para crear el acta de defensa.
3. Registre la **nota individual** de cada jurado y el comentario.
4. El sistema calcula la **nota final** y determina el resultado (aprobado /
   reprobado) según el parámetro de nota mínima aprobado.
5. **Firme** el acta: se registra el presidente, la fecha de firma y el firmante
   responsable.
6. El acta firmada puede **imprimirse** para el expediente físico.

#### 6.2.7 Documentos oficiales

1. Acceda a **Documentos / Plantillas**.
2. Configure las plantillas con su contenido y versión.
3. Genere el documento para una declaración; el sistema asigna un **número
   correlativo** por tipo y año.
4. El contenido generado se conserva como *snapshot*, de modo que las
   modificaciones posteriores de la plantilla no alteran los documentos ya emitidos.

### 6.3 Administrador en el módulo MG

El administrador actúa como **soporte técnico** del módulo, con los mismos permisos
que el coordinador y el auxiliar, además de la gestión de roles MG y del sistema.

---

## 7. Mensajes y notificaciones del sistema

### 7.1 Mensajes flash

Tras cada operación, el sistema muestra un aviso en la parte superior de la pantalla:

| Tipo | Significado |
|---|---|
| **Éxito** (verde) | La operación se completó correctamente. |
| **Error** (rojo) | La operación no se completó; se explica el motivo. |
| **Advertencia** (ámbar) | La operación se completó con observaciones. |
| **Información** (azul) | Mensaje informativo. |

### 7.2 Notificaciones internas

El sistema genera notificaciones automáticas ante eventos relevantes:

| Evento | Notificación |
|---|---|
| Tutor acepta una solicitud | El estudiante es notificado. |
| Tutor inicia o finaliza la sesión | El estudiante es notificado. |
| Tutoría cancelada | Se notifica a la parte afectada. |
| Declaración enviada | El equipo MG recibe el aviso. |
| Declaración aprobada o rechazada | El estudiante recibe el resultado. |
| Aval observado | El estudiante recibe la observación. |

Acceda al ícono de notificaciones para consultar el detalle y marcar los mensajes
como leídos.

---

## 8. Preguntas frecuentes (FAQ)

**¿Cómo recupero mi contraseña?**
Contacte al administrador del sistema. Por seguridad, la contraseña se almacena
 cifrada y no puede recuperarse; el administrador debe establecer una nueva.

**¿Por qué no puedo solicitar una tutoría de una materia?**
Solo se muestran las materias de su carrera que tienen un horario publicado y
asignado a un tutor. Verifique con la administración.

**¿Por qué el sistema no me deja registrarme dos veces en la misma materia?**
Existe una restricción de negocio: un estudiante no puede tener dos solicitudes
activas para la misma materia. Cancele la anterior si desea cambiar de horario.

**¿Puedo cambiar la fecha de una tutoría confirmada?**
La fecha se asigna automáticamente para evitar conflictos de agenda. Para cambiarla,
cancele la solicitud y vuelva a realizarla.

**¿Qué diferencia hay entre una oferta *abierta* y una *asignada*?**
Una oferta *abierta* espera la aceptación de un tutor. Una oferta *asignada* ya tiene
tutor y está disponible para que los estudiantes la soliciten.

**¿Puedo editar una modalidad de grado después de publicarla?**
Sí, el coordinador puede editarla. Los estudiantes que ya la declararon
no se ven afectados.

**¿Qué ocurre si elimino un usuario con registros asociados?**
El sistema impide la eliminación si existen dependencias, para preservar la
integridad referencial. En su lugar, se recomienda desactivar la cuenta.

---

## 9. Glosario

| Término | Definición |
|---|---|
| **Oferta** | Publicación de una materia para tutoría en un turno y nivel determinados. |
| **Turno** | Franja horaria predefinida (Mañana, Mediodía, Tarde, Noche). |
| **Modalidad de grado** | Modalidad académica (proyecto, tesis, trabajo dirigido) que el estudiante debe cursar para graduarse. |
| **Aval** | Firma o respaldo documental de una institución que respalda la declaración. |
| **Expediente** | Conjunto documental y de datos del proceso de una modalidad de grado. |
| **Cohorte** | Agrupación de estudiantes que/licen la modalidad en un mismo periodo. |
| **Jurado** | Tribunal evaluador de la defensa (presidente, titular, suplente). |
| **Correlativo** | Número consecutivo asignado a un documento oficial. |
| **Dashboard** | Panel de indicadores del administrador. |

---

*Fin del Manual de Usuario — Sistema de Tutorías y Modalidades de Grado*
