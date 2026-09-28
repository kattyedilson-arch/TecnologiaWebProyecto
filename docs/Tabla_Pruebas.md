# Casos de Prueba
## Sistema de Tutorías y Modalidades de Grado

**Plan de Pruebas de Software**
**Versión:** 1.0

---

## 1. Introducción

Este documento define el conjunto de casos de prueba del **Sistema de Tutorías y
Modalidades de Grado**. Las pruebas verifican que el sistema satisface los requisitos
funcionales y no funcionales especificados en
[`Requerimientos.md`](Requerimientos.md) y que los casos de uso descritos en
[`Casos_De_Uso.md`](Casos_De_Uso.md) se ejecutan correctamente.

La estrategia de prueba combina técnicas de caja negra —orientadas a verificar el
comportamiento observable del sistema desde la perspectiva del usuario— con
verificaciones de caja blanca sobre los mecanismos críticos de seguridad.

---

## 2. Estrategia de pruebas

### 2.1 Niveles de prueba

| Nivel | Objetivo | Método | Cobertura |
|---|---|---|---|
| **Unitaria** | Verificar funciones y métodos aislados. | Caja blanca | `validador.php`, `funciones.php`, cálculos de nota final. |
| **De integración** | Verificar la interacción entre capas. | Caja negra | Controlador → Modelo → Base de datos. |
| **De sistema** | Verificar el cumplimiento de los requisitos. | Caja negra | Flujos completos de usuario. |
| **De aceptación** | Validar el ajuste a las necesidades del usuario. | Caja negra | Verificación con los roles institucionales. |
| **De seguridad** | Verificar los controles de protección. | Caja negra y blanca | Inyección, CSRF, XSS, control de acceso. |
| **De rendimiento** | Verificar los tiempos de respuesta. | Caja negra | Consultas del dashboard e importaciones. |
| **De usabilidad** | Verificar la claridad de la interfaz. | Caja negra | Mensajes, navegación y formularios. |

### 2.2 Prioridad de las pruebas

| Prioridad | Definición |
|---|---|
| **Alta** | Afecta la operación esencial; su fallo impide usar el sistema. |
| **Media** | Afecta una función secundaria; existe alternativa manual. |
| **Baja** | Mejora la experiencia; no impide la operación. |

---

## 3. Casos de prueba

### 3.1 Autenticación y acceso

| ID | Descripción | Precondición | Pasos | Resultado esperado | Prioridad |
|---|---|---|---|---|---|
| **CP-001** | Iniciar sesión con credenciales válidas | Cuenta activa existe | 1. Abrir login<br>2. Ingresar usuario y contraseña<br>3. Enviar | Sesión iniciada; redirección al panel según el rol | Alta |
| **CP-002** | Iniciar sesión con correo electrónico | Correo registrado | 1. Ingresar correo y contraseña<br>2. Enviar | Sesión iniciada correctamente | Alta |
| **CP-003** | Iniciar sesión con contraseña incorrecta | Cuenta existe | 1. Ingresar contraseña errónea<br>2. Enviar | Mensaje genérico de credenciales incorrectas; intento registrado | Alta |
| **CP-004** | Iniciar sesión con usuario inexistente | — | 1. Ingresar usuario inexistente<br>2. Enviar | Mismo mensaje genérico; no revela la existencia de la cuenta | Alta |
| **CP-005** | Bloqueo por intentos repetidos | IP sin bloqueo previo | 1. Realizar 11 intentos fallidos desde la misma IP | Bloqueo durante 15 minutos con mensaje explicativo | Alta |
| **CP-006** | Acceso a cuenta inactiva | Cuenta con `activo = 0` | 1. Ingresar credenciales correctas de la cuenta inactiva | Acceso denegado con mensaje genérico | Alta |
| **CP-007** | Cierre de sesión | Sesión activa | 1. Pulsar *Cerrar sesión* | Sesión destruida; el acceso directo redirige al login | Alta |
| **CP-008** | Regeneración del identificador de sesión | — | 1. Autenticarse<br>2. Inspeccionar el identificador de sesión | El identificador cambia tras la autenticación | Alta |
| **CP-009** | Redirección por rol | Cinco roles disponibles | 1. Autenticarse con cada rol | Cada rol llega a su panel correspondiente | Media |
| **CP-010** | Registro de auditoría de acceso | Tabla `registro_accesos` | 1. Autenticarse<br>2. Consultar la tabla | Registro con usuario, IP, fecha y resultado | Media |

### 3.2 Seguridad

| ID | Descripción | Precondición | Pasos | Resultado esperado | Prioridad |
|---|---|---|---|---|---|
| **CP-011** | Inyección SQL en el login | Formulario de acceso | 1. Ingresar `' OR '1'='1` como usuario | Acceso denegado; consulta parametrizada sin ejecución del payload | Alta |
| **CP-012** | Inyección SQL en la búsqueda | Sesión iniciada | 1. Enviar `'; DROP TABLE usuarios;--` en un campo de búsqueda | Consulta tratada como texto; la tabla permanece intacta | Alta |
| **CP-013** | Token CSRF ausente | Sesión iniciada | 1. Enviar formulario sin el token | Operación rechazada; ningún dato se modifica | Alta |
| **CP-014** | Token CSRF alterado | Sesión iniciada | 1. Modificar el valor del token en el formulario | Operación rechazada con el mensaje de solicitud expirada | Alta |
| **CP-015** | XSS almacenado | Permiso de escritura | 1. Guardar `<script>alert(1)</script>` en un campo de texto<br>2. Visualizar el registro | El contenido se muestra como texto literal, sin ejecutarse | Alta |
| **CP-016** | XSS reflejado en parámetro de URL | Cualquier página | 1. Acceder a una URL con el payload en el parámetro | El contenido se escapa; no se ejecuta el script | Alta |
| **CP-017** | Acceso directo sin sesión | Sin sesión | 1. Acceder directamente a una URL protegida | Redirección al login | Alta |
| **CP-018** | Acceso a recurso ajeno (tutor) | Dos tutores con tutorías | 1. Como tutor A, intentar consultar la tutoría del tutor B | Acceso denegado; no se revela información del recurso | Alta |
| **CP-019** | Operación de escritura por GET | Sesión iniciada | 1. Enviar una operación de modificación por método GET | Método rechazado; se exige POST | Alta |
| **CP-020** | Escalada de privilegios | Sesión de estudiante | 1. Intentar crear un usuario desde la interfaz | Opción no disponible y denegación en el servidor | Alta |
| **CP-021** | Modificación del propio rol | Sesión de estudiante | 1. Enviar una petición alterando el `rol_id` | El rol no se modifica; el campo se ignora | Alta |
| **CP-022** | Subida de archivo con extensión no permitida | Permiso de carga | 1. Intentar cargar un archivo `.php` | Carga rechazada antes de almacenarse | Media |
| **CP-023** | Cookie de sesión no accesible por JavaScript | Sesión iniciada | 1. Inspeccionar el atributo `HttpOnly` de la cookie | El atributo está activo | Media |
| **CP-024** | Restricción de acceso a la base de datos | Contenedores en ejecución | 1. Intentar conectar al puerto 3306 desde el host | Conexión rechazada; el puerto no está publicado | Media |

### 3.3 Módulo de tutorías — ofertas y disponibilidad

| ID | Descripción | Precondición | Pasos | Resultado esperado | Prioridad |
|---|---|---|---|---|---|
| **CP-025** | Crear oferta de tutoría | Sesión de administrador | 1. Acceder a *Ofertas → Crear*<br>2. Completar materia, nivel, turno y modalidad presencial<br>3. Indicar el lugar<br>4. Guardar | Oferta registrada en estado *abierta* | Alta |
| **CP-026** | Crear oferta virtual sin enlace | Sesión de administrador | 1. Completar la oferta con modalidad virtual<br>2. Dejar vacío el enlace | Rechazo con el error del campo enlace | Alta |
| **CP-027** | Crear oferta con nivel personalizado | Sesión de administrador | 1. Ingresar un nivel no predefinido | Oferta creada con el nivel indicado | Media |
| **CP-028** | Rechazar oferta duplicada | Oferta existente idéntica | 1. Crear la misma combinación de materia, nivel y turno | Rechazo por duplicidad | Media |
| **CP-029** | Aceptar oferta como tutor | Tutor con la materia asignada | 1. Acceder a *Ofertas*<br>2. Pulsar *Aceptar* | Oferta en estado *asignada* con el tutor vinculado | Alta |
| **CP-030** | Rechazar oferta como tutor | Oferta disponible | 1. Pulsar *Rechazar* | Oferta descartada; el administrador notificado | Media |
| **CP-031** | Aceptar oferta de otra materia | Tutor sin esa materia | 1. Intentar aceptar una oferta ajena | La opción no está disponible para el tutor | Alta |
| **CP-032** | Registrar disponibilidad del tutor | Sesión de tutor | 1. Definir día y franja horaria | Disponibilidad registrada para la combinación | Media |
| **CP-033** | Detectar disponibilidad duplicada | Disponibilidad existente | 1. Registrar la misma combinación | Rechazo por duplicidad | Baja |

### 3.4 Módulo de tutorías — solicitud y seguimiento

| ID | Descripción | Precondición | Pasos | Resultado esperado | Prioridad |
|---|---|---|---|---|---|
| **CP-034** | Solicitar tutoría válida | Oferta asignada en la carrera del estudiante | 1. Seleccionar el horario<br>2. Añadir observaciones<br>3. Enviar | Tutoría creada en estado *pendiente* con fecha asignada | Alta |
| **CP-035** | Asignación automática de fecha | Oferta con disponibilidad | 1. Solicitar la tutoría | El sistema asigna el primer día libre del turno | Alta |
| **CP-036** | Rechazo de solicitud sin selección | Oferta disponible | 1. Enviar la solicitud sin elegir horario | Mensaje: se debe seleccionar un horario | Alta |
| **CP-037** | Solicitud de materia ajena a la carrera | Oferta de otra carrera | 1. Intentar solicitar la tutoría | La oferta no se lista; la solicitud se rechaza | Alta |
| **CP-038** | Duplicado de solicitud | Solicitud activa de la misma materia | 1. Intentar solicitar otra tutoría de la materia | Rechazo con aviso de la solicitud existente | Alta |
| **CP-039** | Validación de observaciones extensas | Formulario de solicitud | 1. Ingresar más de 1000 caracteres | Error de longitud máximo | Media |
| **CP-040** | Confirmación de tutoría | Tutoría *pendiente* | 1. Como tutor, pulsar *Confirmar* | Estado *confirmada*; estudiante notificado | Alta |
| **CP-041** | Inicio de sesión de tutoría | Tutoría *confirmada* | 1. Como tutor, pulsar *Iniciar* | Estado *en_proceso* | Alta |
| **CP-042** | Finalización de tutoría | Tutoría *en_proceso* | 1. Como tutor, pulsar *Finalizar* | Estado *realizada* | Alta |
| **CP-043** | Cancelación por el estudiante | Tutoría *pendiente* o *confirmada* | 1. Como estudiante, pulsar *Cancelar* | Estado *cancelada* con motivo registrado | Alta |
| **CP-044** | Rechazo de cancelación tras realización | Tutoría *realizada* | 1. Intentar cancelar | Operación rechazada por estado no válido | Alta |
| **CP-045** | Cambio de estado sin permisos | Tutoría ajena | 1. Como tutor A, intentar cambiar el estado de la tutoría del tutor B | Acceso denegado | Alta |
| **CP-046** | Notificación por cambio de estado | Cualquier transición | 1. Cambiar el estado de una tutoría | Notificación generada para la parte afectada | Media |
| **CP-047** | Validación de transición inválida | Tutoría *pendiente* | 1. Intentar pasar directamente a *realizada* | Rechazo indicando los estados involucrados | Alta |
| **CP-048** | Gestión de estados por el administrador | Cualquier estado | 1. Como administrador, aplicar una transición válida | Estado actualizado correctamente | Alta |

### 3.5 Módulo de tutorías — evaluación e indicadores

| ID | Descripción | Precondición | Pasos | Resultado esperado | Prioridad |
|---|---|---|---|---|---|
| **CP-049** | Evaluar tutoría realizada | Tutoría *realizada* | 1. Seleccionar calificación<br>2. Añadir comentario<br>3. Enviar | Evaluación registrada; refleja en el promedio del tutor | Alta |
| **CP-050** | Evaluación fuera de rango | Formulario de evaluación | 1. Intentar enviar una calificación mayor que 5 | Error de validación del rango | Media |
| **CP-051** | Doble evaluación | Tutoría ya evaluada | 1. Intentar evaluar de nuevo | Operación rechazada; una sola evaluación por tutoría | Media |
| **CP-052** | Evaluación de tutoría no realizada | Tutoría *confirmada* | 1. Intentar evaluar | Operación rechazada por estado no válido | Alta |
| **CP-053** | Visualización del dashboard | Sesión de administrador | 1. Acceder al panel | Se muestran los totales, la distribución por estado y el promedio de evaluaciones | Alta |
| **CP-054** | Ranking de tutores | Evaluaciones registradas | 1. Consultar el ranking | Lista ordenada por promedio de calificación | Media |
| **CP-055** | Materias más solicitadas | Solicitudes registradas | 1. Consultar el indicador | Ranking correcto por número de solicitudes | Media |
| **CP-056** | Desglose por nivel académico | Ofertas de varios niveles | 1. Consultar el desglose | Distribución correcta por nivel | Media |
| **CP-057** | Filtro de actividad reciente | Panel con datos | 1. Filtrar por estado y nivel | Los resultados corresponden a los criterios indicados | Media |
| **CP-058** | Listado de estudiantes del tutor | Tutorías asignadas | 1. Acceder a *Mis estudiantes* | Lista de los estudiantes vinculados a sus tutorías | Alta |

### 3.6 Gestión de usuarios, roles y catálogos

| ID | Descripción | Precondición | Pasos | Resultado esperado | Prioridad |
|---|---|---|---|---|---|
| **CP-059** | Crear usuario | Sesión de administrador | 1. Completar el formulario con datos válidos<br>2. Guardar | Usuario creado con rol asignado | Alta |
| **CP-060** | Usuario con correo duplicado | Correo ya registrado | 1. Crear un usuario con ese correo | Rechazo por duplicidad | Alta |
| **CP-061** | Almacenamiento seguro de la contraseña | Usuario creado | 1. Consultar el campo de contraseña en la base de datos | Solo se almacena el hash bcrypt, nunca el texto plano | Alta |
| **CP-062** | Crear carrera | Sesión de administrador | 1. Ingresar el código y el nombre | Carrera registrada | Media |
| **CP-063** | Crear materia | Sesión de administrador | 1. Ingresar código, nombre y créditos | Materia registrada | Media |
| **CP-064** | Asignar tutor a materia | Sesión de administrador | 1. Registrar la asignación | La relación queda establecida | Media |
| **CP-065** | Eliminar carrera con estudiantes | Carrera con estudiantes | 1. Intentar eliminarla | Operación bloqueada por integridad referencial | Alta |
| **CP-066** | Desactivar usuario con historial | Usuario con registros | 1. Desactivar la cuenta | La cuenta queda inactiva; el historial se conserva | Alta |

### 3.7 Módulo de modalidades de grado — catálogo y declaración

| ID | Descripción | Precondición | Pasos | Resultado esperado | Prioridad |
|---|---|---|---|---|---|
| **CP-067** | Registrar modalidad de grado | Sesión de coordinador | 1. Completar código, nombre y tipo<br>2. Guardar | Modalidad registrada | Alta |
| **CP-068** | Publicar modalidad | Modalidad registrada | 1. Pulsar *Publicar* | Visible para la declaración de estudiantes | Alta |
| **CP-069** | Declaración en periodo cerrado | Periodo cerrado | 1. Intentar declarar | Operación rechazada; solo en periodo abierto | Alta |
| **CP-070** | Declaración de modalidad no publicada | Modalidad inactiva | 1. Intentar declararla | La modalidad no aparece en el catálogo disponible | Alta |
| **CP-071** | Guardar borrador de declaración | Estudiante con sesión | 1. Completar los datos<br>2. Guardar como borrador | Declaración en estado *borrador* | Alta |
| **CP-072** | Enviar declaración | Borrador completo | 1. Enviar formalmente | Estado *enviada*; notificación al equipo MG | Alta |
| **CP-073** | Declaración duplicada | Declaración activa de la misma modalidad | 1. Intentar declarar de nuevo | Rechazo por duplicidad en el mismo periodo | Alta |
| **CP-074** | Validación de longitud del título | Formulario de declaración | 1. Ingresar un título de más de 200 caracteres | Error de longitud máxima | Media |
| **CP-075** | Validación del resumen | Formulario de declaración | 1. Ingresar un resumen de más de 2000 caracteres | Error de longitud máxima | Media |

### 3.8 Módulo de modalidades de grado — operación

| ID | Descripción | Precondición | Pasos | Resultado esperado | Prioridad |
|---|---|---|---|---|---|
| **CP-076** | Registrar aval | Declaración en *enviada* | 1. Añadir el aval con sus datos | Aval registrado en estado *pendiente* | Alta |
| **CP-077** | Marcar aval como entregado | Aval pendiente | 1. Adjuntar el documento<br>2. Marcar como entregado | Estado *entregado* con la ruta del archivo | Alta |
| **CP-078** | Observar un aval | Aval pendiente | 1. Registrar la observación | Estado *observado*; estudiante notificado | Alta |
| **CP-079** | Revisión de declaración | Declaración *enviada* | 1. Como auxiliar, iniciar la revisión | Estado *en_revision* | Alta |
| **CP-080** | Aprobación sin avales completos | Avales pendientes | 1. Intentar aprobar | Operación bloqueada; se señalan los avales faltantes | Alta |
| **CP-081** | Aprobación de declaración | Todos los avales *entregados* | 1. Como coordinador, aprobar | Estado *aprobada*; estudiante notificado | Alta |
| **CP-082** | Rechazo sin observación | Declaración *en_revision* | 1. Intentar rechazar sin indicar el motivo | Rechazo con error de campo obligatorio | Alta |
| **CP-083** | Aprobación por el auxiliar | Declaración *en_revision* | 1. Como auxiliar, intentar aprobar | Acceso denegado; la aprobación es exclusiva del coordinador | Alta |
| **CP-084** | Importar padrón CSV válido | Archivo con filas correctas | 1. Seleccionar el archivo<br>2. Ejecutar la importación | Expedientes creados; resumen con filas `ok` | Alta |
| **CP-085** | Importar archivo con formato incorrecto | Archivo no CSV | 1. Intentar importar | Rechazo con mensaje de formato | Media |
| **CP-086** | Fila sin cuenta de usuario | Fila con estudiante no registrado | 1. Importar el padrón | Fila con resultado `pendiente_cuenta`; el resto se procesa | Alta |
| **CP-087** | Fila duplicada | Fila ya importada | 1. Reimportar el padrón | Fila con resultado `omitida`; no se duplica el expediente | Alta |
| **CP-088** | Tolerancia a error por fila | Padrón con una fila inválida | 1. Importar el padrón | El error afecta solo a esa fila; las demás se crean | Alta |
| **CP-089** | Asignar tutor de modalidad | Declaración aprobada | 1. Seleccionar el tutor<br>2. Registrar la carta | Asignación registrada en estado *vigente* | Alta |
| **CP-090** | Reasignación de tutor | Asignación vigente | 1. Asignar otro tutor | La asignación anterior queda *reemplazada* y se conserva en el historial | Alta |
| **CP-091** | Modalidad que no requiere tutor | Declaración de modalidad sin tutor | 1. Intentar asignar tutor | La asignación no es exigida por el proceso | Media |
| **CP-092** | Registrar etapa del expediente | Expediente activo | 1. Registrar el inicio de la etapa | Etapa registrada con fecha y responsable | Alta |
| **CP-093** | Cierre de etapa con resultado | Etapa activa | 1. Registrar el cierre y el resultado | Etapa cerrada con resultado y observaciones | Alta |
| **CP-094** | Transición terminal de expediente | Etapa activa | 1. Registrar *abandono* o *reprobado* | Estado terminal registrado en el expediente | Alta |
| **CP-095** | Asignar jurado | Declaración con expediente avanzado | 1. Registrar presidente, titular y suplente | Tribunal asignado con sus roles | Alta |
| **CP-096** | Registro de acta con notas | Jurado asignado | 1. Ingresar la nota de cada jurado | Se calcula la nota final y el resultado | Alta |
| **CP-097** | Resultado del acta según nota mínima | Acta con nota final calculada | 1. Firmar el acta | El resultado corresponde al parámetro de nota mínima aprobada | Alta |
| **CP-098** | Firma y sellado del acta | Acta sin firmar | 1. Firmar el acta | Se registra el presidente y la fecha de firma | Alta |
| **CP-099** | Modificación de acta firmada | Acta firmada | 1. Intentar modificar el contenido | Operación rechazada; el acta queda inalterable | Alta |
| **CP-100** | Generación de documento oficial | Declaración con expediente | 1. Seleccionar la plantilla<br>2. Generar | Documento emitido con correlativo único | Media |
| **CP-101** | Correlativo consecutivo | Varios documentos emitidos | 1. Generar dos documentos del mismo tipo | Correlativos correlativos y únicos | Media |
| **CP-102** | Instantánea del documento emitido | Documento generado | 1. Modificar la plantilla<br>2. Consultar el documento anterior | El documento previo conserva su contenido original | Media |
| **CP-103** | Versiones de plantilla | Plantilla existente | 1. Crear una nueva versión | Quedan registradas; solo una puede estar activa | Media |
| **CP-104** | Configuración de parámetros | Sesión de coordinador | 1. Modificar la nota mínima aprobada | El valor se guarda y se aplica a las actas siguientes | Media |
| **CP-105** | Parámetro técnico no editable | Parámetro de solo lectura | 1. Intentar modificarlo | El campo se muestra deshabilitado | Baja |

### 3.9 Interfaz y usabilidad

| ID | Descripción | Precondición | Pasos | Resultado esperado | Prioridad |
|---|---|---|---|---|---|
| **CP-106** | Menú lateral por rol | Sesión iniciada | 1. Navegar por el sistema con cada rol | Cada rol visualiza únicamente sus módulos | Alta |
| **CP-107** | Mensaje de éxito | Operación exitosa | 1. Completar una operación | Mensaje de confirmación visible | Media |
| **CP-108** | Mensaje de error de validación | Datos incorrectos | 1. Enviar el formulario | Mensaje por campo incorrecto | Media |
| **CP-109** | Confirmación de acción destructiva | Operación de eliminación | 1. Pulsar eliminar | Se solicita confirmación antes de eliminar | Media |
| **CP-110** | Contador de notificaciones | Notificaciones sin leer | 1. Acceder al sistema | El ícono muestra la cantidad de notificaciones pendientes | Baja |
| **CP-111** | Diseño responsivo | Distintos tamaños de pantalla | 1. Visualizar en móvil, tableta y escritorio | La interfaz se adapta sin pérdida de funcionalidad | Media |
| **CP-112** | Consistencia de navegación | Navegación por módulos | 1. Recorrer los módulos | La estructura de menús es homogénea | Baja |

### 3.10 Rendimiento y despliegue

| ID | Descripción | Precondición | Pasos | Resultado esperado | Prioridad |
|---|---|---|---|---|---|
| **CP-113** | Tiempo de carga del dashboard | 10 000 registros | 1. Acceder al panel y cronometrar | Respuesta inferior a 2 segundos | Media |
| **CP-114** | Tiempo de carga de página | Cualquier página | 1. Solicitar la página y cronometrar | Respuesta inferior a 3 segundos | Baja |
| **CP-115** | Consulta con índice | Panel de tutorías | 1. Filtrar por fecha y estado | La consulta utiliza los índices definidos | Media |
| **CP-116** | Importación de padrón extenso | Archivo de 500 filas | 1. Importar el padrón | Procesamiento completo sin bloquear la interfaz | Media |
| **CP-117** | Despliegue con Docker | Docker instalado | 1. Ejecutar `docker compose up -d` | El sistema queda operativo sin configuración manual | Alta |
| **CP-118** | Persistencia de datos | Contenedores en ejecución | 1. Detener y volver a levantar los contenedores | Los datos se conservan en el volumen | Alta |
| **CP-119** | Migraciones idempotentes | Esquema inicializado | 1. Re-ejecutar las migraciones | No producen errores ni efectos adversos | Media |
| **CP-120** | Migración de nivel académico | Migración 017 | 1. Aplicar la migración | La columna `nivel_academico` queda disponible en las ofertas | Alta |

---

## 4. Resumen de cobertura

| Módulo | Casos de prueba | Funcionalidad crítica |
|---|---|---|
| Autenticación y acceso | 10 | Acceso seguro al sistema |
| Seguridad | 14 | Protección frente a ataques comunes |
| Ofertas y disponibilidad | 9 | Publicación de tutorías |
| Solicitud y seguimiento | 15 | Ciclo de vida de la tutoría |
| Evaluación e indicadores | 10 | Valoración y gestión |
| Usuarios, roles y catálogos | 8 | Administración institucional |
| MG: catálogo y declaración | 9 | Configuración y declaración |
| MG: operación | 30 | Gestión del proceso de grado |
| Interfaz y usabilidad | 7 | Calidad de la interacción |
| Rendimiento y despliegue | 8 | Desempeño y operación |
| **Total** | **120** | — |

### 4.1 Distribución por prioridad

| Prioridad | Casos | Porcentaje |
|---|---|---|
| **Alta** | 78 | 65.0% |
| **Media** | 37 | 30.8% |
| **Baja** | 5 | 4.2% |

### 4.2 Distribución por tipo de prueba

| Tipo | Casos | Porcentaje |
|---|---|---|
| **Funcional** | 106 | 88.3% |
| **Seguridad** | 14 | 11.7% |

---

## 5. Matriz de trazabilidad casos–requisitos

| Requisito | Casos de prueba asociados |
|---|---|
| RF-001 – RF-006 | CP-001, CP-002, CP-003, CP-004, CP-006, CP-007, CP-009 |
| RF-007 – RF-011 | CP-010, CP-013, CP-014, CP-005 |
| RF-012 – RF-020 | CP-059, CP-060, CP-062, CP-063, CP-064, CP-065, CP-066 |
| RF-021 – RF-027 | CP-025, CP-026, CP-027, CP-028, CP-029, CP-030, CP-031, CP-032, CP-033 |
| RF-028 – RF-034 | CP-034, CP-035, CP-036, CP-037, CP-038, CP-039, CP-040, CP-041, CP-042 |
| RF-035 – RF-041 | CP-043, CP-044, CP-045, CP-046, CP-047, CP-048, CP-058 |
| RF-042 – RF-050 | CP-053, CP-054, CP-055, CP-056, CP-057 |
| RF-051 – RF-058 | CP-067, CP-068, CP-104, CP-105 |
| RF-059 – RF-066 | CP-069, CP-070, CP-071, CP-072, CP-073, CP-074, CP-075, CP-076, CP-077, CP-078, CP-079, CP-080, CP-081, CP-082, CP-083 |
| RF-067 – RF-069 | CP-084, CP-085, CP-086, CP-087, CP-088, CP-116 |
| RF-070 – RF-071 | CP-089, CP-090, CP-091 |
| RF-072 – RF-073 | CP-092, CP-093, CP-094 |
| RF-074 – RF-077 | CP-095, CP-096, CP-097, CP-098, CP-099 |
| RF-078 – RF-080 | CP-100, CP-101, CP-102, CP-103 |
| RF-081 – RF-083 | CP-083, CP-106, CP-020, CP-021 |
| RNF-006 – RNF-010 | CP-061, CP-011, CP-012, CP-015, CP-016, CP-013 |
| RNF-011 | CP-017, CP-018, CP-019, CP-020, CP-021, CP-045 |
| RNF-013 | CP-005 |
| RNF-015 | CP-022 |
| RNF-001 – RNF-005 | CP-113, CP-114, CP-115, CP-116 |
| RNF-030 – RNF-033 | CP-117, CP-118, CP-119, CP-120, CP-024 |
| RNF-016 – RNF-021 | CP-106, CP-107, CP-108, CP-109, CP-111, CP-112 |
| RNF-003 | CP-115 |

---

## 6. Datos de prueba

### 6.1 Cuentas de prueba

| Rol | Usuario | Contraseña | Uso en pruebas |
|---|---|---|---|
| Administrador | `admin` | `password` | Gestión total y dashboard |
| Tutor | `tutor1` … `tutor10` | `password` | Ofertas, disponibilidad y estados |
| Estudiante | `estudiante1` … `estudiante50` | `password` | Solicitudes y evaluaciones |
| Coordinador MG | Según asignación institucional | — | Aprobación y coordinación |
| Auxiliar MG | Según asignación institucional | — | Avales, expedientes y actas |

### 6.2 Escenarios de prueba

| Escenario | Descripción |
|---|---|
| **Ciclo completo de tutoría** | Solicitud → confirmación → proceso → realización → evaluación. |
| **Ciclo completo de modalidad** | Declaración → avales → revisión → aprobación → expediente → acta. |
| **Cancelación en cada estado** | Verificación de las transiciones permitidas desde *pendiente* y *confirmada*. |
| **Recuperación de contraseña** | Flujo de restablecimiento mediante el correo registrado. |
| **Importación con errores** | Padrón que mezcla filas válidas, duplicadas y sin cuenta. |

---

## 7. Criterios de entrada y de salida

### 7.1 Criterios de entrada

| # | Criterio |
|---|---|
| 1 | El sistema se encuentra desplegado y operativo. |
| 2 | La base de datos está inicializada con el esquema y los datos de prueba. |
| 3 | Existen las cuentas de prueba necesarias para cada rol. |
| 4 | El documento de requerimientos está aprobado y vigente. |

### 7.2 Criterios de salida

| # | Criterio |
|---|---|
| 1 | Todos los casos de prioridad alta han sido ejecutados. |
| 2 | Ningún caso de prioridad alta presenta errores. |
| 3 | Los errores detectados de prioridad media están documentados. |
| 4 | Los requisitos no funcionales se cumplen según sus criterios de aceptación. |
| 5 | El manual de usuario refleja el comportamiento verificado. |

---

*Fin del documento de Casos de Prueba*
