# Arquitectura del Sistema
## Sistema de Tutorías y Modalidades de Grado

**Documento de Arquitectura de Software**
**Versión:** 1.0

---

## 1. Introducción

Este documento describe la arquitectura del **Sistema de Tutorías y Modalidades de
Grado**. La arquitectura define la estructura técnica del sistema: sus capas, sus
componentes, sus responsabilidades y las relaciones entre ellos.

El sistema fue diseñado siguiendo el patrón de arquitectura **Modelo-Vista-Controlador
(MVC)**, implementado de forma nativa en PHP sin el uso de frameworks externos. Esta
decisión responde a las restricciones del proyecto, que exigen demostrar el
dominio de los fundamentos del desarrollo web en PHP.

---

## 2. Principios arquitectónicos

| Principio | Descripción |
|---|---|
| **Separación de responsabilidades** | Cada capa tiene una función específica y no invaden las competencias de las demás. |
| **Bajo acoplamiento** | Las capas se comunican mediante interfaces simples (Modelos, Vistas, Controladores). |
| **Alta cohesión** | Cada componente agrupa functionalities relacionadas. |
| **Reutilización** | La lógica común se centraliza en funciones auxiliares reutilizables. |
| **Seguridad por diseño** | La validación y el escape se aplican de forma sistemática en todas las capas. |
| **Portabilidad** | El sistema se despliega íntegramente mediante contenedores Docker. |

---

## 3. Vista general de la arquitectura

### 3.1 Diagrama de arquitectura

```mermaid
graph TB
    %% ===== Capa de Presentación =====
    subgraph CLIENTE["🖥️ Cliente (Navegador)"]
        HTML["HTML5 / CSS3"]
        VUE["Vue.js 3 (CDN)"]
        JS["JavaScript nativo"]
        FETCH["Fetch API / AJAX"]
    end

    %% ===== Capa de Aplicación =====
    subgraph SERVIDOR["🖧 Servidor Web - Apache + PHP 8.2"]

        subgraph PRESENTACION["Capa de Presentación (Vistas)"]
            VIEWS["Vistas PHP<br/>dashboard, tutorias, mg, auth"]
            LAYOUT["Layouts y componentes<br/>nav, modales, mensajes"]
        end

        subgraph CONTROL["Capa de Control (Controladores)"]
            AUTH_CTRL["Controladores de Autenticación<br/>login, registro, recuperación"]
            TUT_CTRL["Controladores de Tutorías<br/>ofertas, solicitudes, estados"]
            DASH_CTRL["Controladores de Dashboard<br/>indicadores, filtros"]
            MG_CTRL["Controladores MG<br/>declaraciones, expedientes, actas"]
            USR_CTRL["Controladores de Usuarios<br/>CRUD, roles, catálogos"]
        end

        subgraph NEGOCIO["Capa de Negocio / Núcleo"]
            VALID["Validador<br/>Esquemas de validación"]
            AUTH["Gestor de Sesión<br/>y Autenticación"]
            PERMISOS["Gestor de Permisos<br/>Matriz por rol"]
            NOTIF["Servicio de Notificaciones"]
        end

        subgraph INFRA["Capa de Infraestructura"]
            DB["Capa de Datos (Modelos PDO)"]
            HELPERS["Funciones Auxiliares<br/>escape, CSRF, flash"]
        end
    end

    %% ===== Capa de Datos =====
    subgraph DATOS["🗄️ MySQL 8.0"]
        MYSQL[("Motor de Base de Datos<br/>InnoDB / utf8mb4")]
    end

    %% ===== Flujo de datos =====
    HTML --> VUE
    VUE --> FETCH
    FETCH -->|"Peticiones HTTP"| AUTH_CTRL
    FETCH -->|"Peticiones HTTP"| TUT_CTRL
    FETCH -->|"Peticiones HTTP"| DASH_CTRL
    FETCH -->|"Peticiones HTTP"| MG_CTRL
    FETCH -->|"Peticiones HTTP"| USR_CTRL

    AUTH_CTRL --> CONTROL
    TUT_CTRL --> CONTROL
    DASH_CTRL --> CONTROL
    MG_CTRL --> CONTROL
    USR_CTRL --> CONTROL

    CONTROL --> NEGOCIO
    NEGOCIO --> DB
    DB --> MYSQL

    CONTROL --> PRESENTACION
    PRESENTACION -->|"HTML + JSON"| CLIENTE

    %% ===== Notas de seguridad =====
    SEC["🔒 Capas de seguridad<br/>CSRF · Escape · Validación · bcrypt"] -.-> CONTROL
    SEC -.-> DB
```

### 3.2 Descripción de las capas

| Capa | Ubicación | Responsabilidad |
|---|---|---|
| **Cliente** | Navegador del usuario | Presentar la interfaz, capturar la entrada del usuario e interactuar mediante AJAX. |
| **Vistas** | `views/` | Generar el HTML que se envía al cliente. No contienen lógica de negocio. |
| **Controladores** | `controllers/` | Atender las peticiones HTTP, coordinar el flujo y delegar en los modelos. |
| **Negocio** | `includes/`, `helpers/` | Aplicar validación, autenticación, permisos y lógica transversal. |
| **Modelos** | `models/` | Ejecutar consultas SQL parametrizadas y mapear resultados. |
| **Datos** | MySQL 8.0 | Persistir la información y garantizar la integridad referencial. |

---

## 4. Estructura de directorios

```text
TecnologiaWebProyecto/
├── config/
│   ├── config.php              # Constantes y rutas del proyecto
│   └── database.php            # Parámetros de conexión ( variables de entorno)
│
├── controllers/                # Capa de Control
│   ├── auth/                   # Autenticación y registro
│   ├── dashboard/              # Indicadores y reportes
│   ├── tutorias/               # Ofertas, solicitudes, estados
│   ├── mg/                     # Modalidades de grado
│   ├── admin/                  # Usuarios y catálogos
│   └── ajax/                   # Peticiones asíncronas
│
├── models/                     # Capa de Modelo
│   ├── UsuarioModel.php
│   ├── TutorModel.php
│   ├── EstudianteModel.php
│   ├── TutoriaModel.php
│   ├── OfertaModel.php
│   ├── DashboardModel.php
│   └── mg/                     # Modelos del módulo MG
│
├── views/                      # Capa de Presentación
│   ├── layouts/                # Plantillas base
│   ├── partials/               # Componentes reutilizables
│   ├── auth/                   # Vistas de acceso
│   ├── dashboard/              # Vistas del panel
│   ├── tutorias/               # Vistas de tutorías
│   ├── mg/                     # Vistas de modalidades
│   └── admin/                  # Vistas administrativas
│
├── includes/                   # Capa de Infraestructura y Núcleo
│   ├── config.php
│   ├── database.php            # Clase de conexión PDO
│   ├── funciones.php           # Utilidades (escape, CSRF, sesión, flash)
│   ├── permisos.php            # Matriz de permisos por rol
│   ├── auth.php                # Guardas de autenticación
│   └── validador.php           # API de validación
│
├── public/                     # Raíz web (document root)
│   ├── index.php               # Front controller
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── uploads/                # Documentos adjuntos
│
├── database/
│   ├── init.sql                # Esquema base y datos iniciales
│   └── migrations/             # Migraciones versionadas
│
├── docs/                       # Documentación técnica
├── tests/                      # Pruebas
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

## 5. Patrón Modelo-Vista-Controlador

### 5.1 Modelo

Los modelos encapsulan el acceso a la base de datos. Cada modelo corresponde a una
entidad o a un conjunto de entidades y expone métodos que devuelven arrays asociativos.

```php
// Patrón general de un modelo
class TutoriaModel {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function buscarPorId($id) {
        $sql = "SELECT * FROM tutorias WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
```

**Características**
- Solo usan **consultas preparadas PDO**, lo que elimina la inyección SQL.
- No contienen lógica de presentación ni salidas HTML.
- Se pueden probar de forma aislada.

### 5.2 Controlador

Los controladores atienden las peticiones, validan la entrada del usuario y coordinan
el flujo entre modelos y vistas.

```php
// Patrón general de un controlador
class TutoriasController {
    private $modelo;

    public function __construct() {
        $this->modelo = new TutoriaModel(db());
    }

    public function listar() {
        verificarPermiso('ver_tutorias');
        $tutorias = $this->modelo->listarTodas();
        require VIEWS_PATH . '/tutorias/index.php';
    }
}
```

**Características**
- Verifican el token CSRF en las operaciones de escritura.
- Aplican la matriz de permisos antes de ejecutar la acción.
- Devuelven respuestas HTML o JSON según el tipo de petición.

### 5.3 Vista

Las vistas generan el HTML que se envía al navegador. Aplican escape a toda salida
dinámica para prevenir ataques XSS.

```php
<!-- Patrón general de una vista -->
<h2><?= e($titulo) ?></h2>
<?php foreach ($tutorias as $t): ?>
    <tr>
        <td><?= e($t['materia']) ?></td>
        <td><?= e($t['fecha']) ?></td>
    </tr>
<?php endforeach; ?>
```

---

## 6. Patrón Front Controller

Todo el tráfico HTTP se dirige a un único punto de entrada: `public/index.php`. Este
mecanismo centraliza el arranque de la aplicación.

```mermaid
graph LR
    REQ["Petición HTTP"] --> FC["public/index.php<br/>Front Controller"]
    FC --> BOOT["Inicialización<br/>· Carga configuración<br/>· Conexión PDO<br/>· Inicio de sesión<br/>· Autocarga"]
    BOOT --> ROUTE["Enrutador<br/>Resolución de ruta"]
    ROUTE --> MID["Middleware<br/>· Autenticación<br/>· Permisos"]
    MID --> CTRL["Controlador"]
    CTRL --> MODEL["Modelo"]
    MODEL --> DB[("MySQL")]
    CTRL --> VIEW["Vista"]
    VIEW --> RESP["Respuesta HTTP<br/>HTML o JSON"]
    RESP --> REQ
```

**Ventajas del patrón**
- Punto único de control sobre la seguridad y el flujo de la aplicación.
- Evita la exposición de archivos sensibles del directorio del proyecto.
- Simplifica la incorporación de middlewares transversales.

---

## 7. Modelo de datos a nivel de arquitectura

```mermaid
graph TB
    subgraph IDENT["Subsistema de Identidad"]
        U["usuarios"]
        R["roles"]
        A["registro_accesos"]
    end

    subgraph INST["Subsistema Institucional"]
        C["carreras"]
        M["materias"]
        T["turnos"]
        TC["tutor_materia"]
    end

    subgraph TUT["Subsistema de Tutorías"]
        OF["ofertas_tutoria"]
        DIS["disponibilidad_tutor"]
        TU["tutorias"]
        EV["evaluaciones"]
        NT["notificaciones"]
    end

    subgraph MGS["Subsistema de Modalidades de Grado"]
        MOD["mg_modalidades"]
        PE["mg_periodos"]
        CO["mg_cohortes"]
        DE["mg_declaraciones"]
        AV["mg_avales"]
        EX["mg_expedientes"]
        ET["mg_expediente_etapas"]
        AS["mg_asignacion_tutores"]
        JU["mg_jurados"]
        AC["mg_actas"]
        DF["mg_documentos"]
        PL["mg_parametros"]
        IM["mg_importaciones"]
    end

    R --> U
    C --> U
    U --> TC
    M --> TC
    U --> TU
    M --> OF
    T --> OF
    M --> DIS
    T --> DIS
    OF --> TU
    TU --> EV
    U --> NT
    MOD --> DE
    U --> DE
    PE --> DE
    CO --> DE
    DE --> AV
    DE --> EX
    EX --> ET
    DE --> AS
    U --> AS
    DE --> JU
    DE --> AC
    DE --> DF
    MOD --> DF
    DE --> IM
```

---

## 8. Arquitectura de la base de datos

### 8.1 Esquema general

La base de datos se organiza en cuatro dominios funcionales claramente delimitados:

| Dominio | Tablas principales | Responsabilidad |
|---|---|---|
| **Identidad** | `usuarios`, `roles`, `registro_accesos`, `notificaciones` | Identificación, sesión y auditoría de accesos. |
| **Institucional** | `carreras`, `materias`, `turnos`, `tutor_materia` | Estructura académica de la institución. |
| **Tutorías** | `ofertas_tutoria`, `disponibilidad_tutor`, `tutorias`, `evaluaciones` | Ciclo completo de la tutoría. |
| **Modalidades de Grado** | `mg_modalidades`, `mg_declaraciones`, `mg_expedientes`, `mg_actas`, entre otras | Proceso de modalidades de grado. |

### 8.2 Motor de almacenamiento

| Característica | Configuración |
|---|---|
| **Motor** | InnoDB |
| **Codificación** | `utf8mb4_unicode_ci` |
| **Integridad referencial** | Claves foráneas con `ON DELETE` y `ON UPDATE` definidos |
| **Restricciones de dominio** | Campos `ENUM` para estados controlados |
| **Claves compuestas** | Restricciones `UNIQUE` en combinaciones de negocio |
| **Migraciones** | Archivos versionados con registro en `schema_migrations` |

### 8.3 Estrategia de indices

| Índice | Propósito |
|---|---|
| `idx_*_fecha` | Acelera los filtros cronológicos del dashboard y los reportes. |
| `idx_*_estado` | Optimiza la consulta de registros por estado. |
| `idx_tutorias_tutor` | Acelera el acceso del tutor a sus registros. |
| `idx_tutorias_estudiante` | Acelera el acceso del estudiante a sus registros. |
| `UNIQUE` compuestos | Impiden duplicados en catálogos y combinaciones de negocio. |

---

## 9. Arquitectura de seguridad

```mermaid
graph TB
    subgraph CAPA1["🛡️ Capa 1 · Entrada y Sesión"]
        S1["Arranque seguro de sesión<br/>Cookies HttpOnly y SameSite"]
        S2["Control de acceso<br/>Autenticación obligatoria"]
        S3["Anti-fuerza bruta<br/>10 intentos / 15 min / IP"]
    end

    subgraph CAPA2["🛡️ Capa 2 · Formularios"]
        S4["Token anti-CSRF<br/>random_bytes + hash_equals"]
        S5["Método POST obligatorio<br/>para operaciones de escritura"]
    end

    subgraph CAPA3["🛡️ Capa 3 · Controladores"]
        S6["Matriz de permisos<br/>por rol"]
        S7["Validación de entrada<br/>Esquemas declarativos"]
    end

    subgraph CAPA4["🛡️ Capa 4 · Modelos y Datos"]
        S8["Consultas preparadas PDO<br/>100% parametrizadas"]
        S9["bcrypt para contraseñas<br/>password_hash / password_verify"]
    end

    subgraph CAPA5["🛡️ Capa 5 · Salida"]
        S10["Escape de salida<br/>htmlspecialchars"]
        S11["Auditoría<br/>registro_accesos"]
    end

    CAPA1 --> CAPA2 --> CAPA3 --> CAPA4 --> CAPA5
```

Los detalles completos de estas medidas se encuentran en
[`Seguridad.md`](Seguridad.md).

---

## 10. Patrón de flujo de una petición

```mermaid
sequenceDiagram
    participant U as Usuario
    participant B as Navegador
    participant F as Front Controller
    participant C as Controlador
    participant V as Validador
    participant P as Gestor de Permisos
    participant M as Modelo
    participant DB as MySQL
    participant W as Vista

    U->>B: Envía formulario (POST + token CSRF)
    B->>F: Petición HTTP
    F->>F: Inicia sesión, carga configuración
    F->>C: Enruta al controlador
    C->>P: Verifica permiso del rol
    alt Sin permiso
        P-->>C: Denegado
        C-->>B: 403 / mensaje de error
    else Con permiso
        P-->>C: Autorizado
        C->>V: Valida la entrada
        alt Entrada inválida
            V-->>C: Errores de validación
            C-->>B: Errores por campo
        else Entrada válida
            V-->>C: Datos válidos
            C->>M: Ejecuta la operación
            M->>DB: Consulta preparada PDO
            DB-->>M: Resultado
            M-->>C: Datos
            C->>W: Renderiza la respuesta
            W-->>B: HTML escapado / JSON
            B-->>U: Muestra el resultado
        end
    end
```

---

## 11. Arquitectura de despliegue

```mermaid
graph TB
    subgraph HOST["🖥️ Máquina anfitriona"]
        subgraph DOCKER["🐳 Docker"]
            subgraph APACHE["Contenedor: web"]
                PHPP["PHP 8.2 + Apache 2.4"]
                DOCROOT["/var/www/html → public/"]
                MODPHP["Módulos PHP<br/>pdo_mysql, mbstring, gd"]
            end

            subgraph MYSQLC["Contenedor: db"]
                MYSQLD[("MySQL 8.0<br/>Volumen persistente")]
            end
        end
    end

    USER["👤 Usuario"] -->|"http://localhost:8080"| APACHE
    APACHE -->|"Puerto 3306 (red interna)"| MYSQLC

    subgraph INIT["⚙️ Inicialización Automática"]
        I1["init.sql<br/>Esquema y datos iniciales"]
        I2["migrations/*.sql<br/>Ejecución versionada"]
        I3["schema_migrations<br/>Control de versiones"]
        I1 --> I2 --> I3
    end

    INIT -.->|"Carga automática<br/>al iniciar el contenedor"| MYSQLC
```

### 11.1 Componentes de despliegue

| Componente | Tecnología | Función |
|---|---|---|
| **Servidor web** | Apache 2.4 | Publica el punto de entrada y reescribe las URL. |
| **Lenguaje** | PHP 8.2 | Ejecuta la lógica de la aplicación. |
| **Base de datos** | MySQL 8.0 | Almacena y relaciona la información. |
| **Orquestación** | Docker Compose | Levanta y conecta los servicios. |
| **Volumen de datos** | Volumen nombrado | Persiste la base de datos entre reinicios. |

### 11.2 Variables de entorno

La configuración se externaliza del código mediante variables de entorno:

| Variable | Descripción |
|---|---|
| `DB_HOST` | Servidor de la base de datos. |
| `DB_PORT` | Puerto de conexión. |
| `DB_NAME` | Nombre de la base de datos. |
| `DB_USER` | Usuario de conexión. |
| `DB_PASS` | Contraseña de conexión. |
| `APP_DEBUG` | Modo de depuración. |
| `DB_PERSISTENT` | Habilita conexiones persistentes. |

---

## 12. Decisiones arquitectónicas

| # | Decisión | Justificación |
|---|---|---|
| 1 | **MVC nativo en PHP** | Cumplir la restricción de no usar frameworks y evidenciar dominio de los fundamentos. |
| 2 | **Front controller único** | Centralizar la seguridad y evitar la exposición de archivos internos. |
| 3 | **PDO con consultas preparadas** | Eliminar la inyección SQL de forma estructural, no por disciplina del programador. |
| 4 | **Vue.js por CDN** | Interactividad sin proceso de compilación, reduciendo la complejidad del despliegue. |
| 5 | **Migraciones versionadas** | Permitir la evolución del esquema de forma reproducible y auditable. |
| 6 | **Parámetros en base de datos** | Evitar la modificación de reglas de negocio en el código fuente. |
| 7 | **Campos ENUM para estados** | Aplicar las restricciones de dominio directamente en el motor de base de datos. |
| 8 | **Docker Compose** | Garantizar un despliegue reproducible e independiente del sistema operativo. |
| 9 | **Tabla de historial de asignaciones** | Preservar la trazabilidad sin eliminar registros previos. |
| 10 | **Instantáneas de documentos** | Conservar el contenido emitido aunque la plantilla cambie posteriormente. |

---

## 13. Vista de privilegios por rol

| Componente | Administrador | Tutor | Estudiante | Coordinador MG | Auxiliar MG |
|---|---|---|---|---|---|
| **Dashboard institucional** | Acceso total | — | — | — | — |
| **Gestión de usuarios** | Acceso total | — | — | — | — |
| **Gestión de catálogos** | Acceso total | — | — | — | — |
| **Crear ofertas** | Acceso | — | — | — | — |
| **Aceptar ofertas** | — | Acceso | — | — | — |
| **Configurar disponibilidad** | — | Acceso | — | — | — |
| **Solicitar tutoría** | — | — | Acceso | — | — |
| **Evaluar tutoría** | — | — | Acceso | — | — |
| **Gestionar estados** | Acceso | Solo propias | Solo cancelación | — | — |
| **Registrar modalidad** | Acceso | — | — | Acceso | — |
| **Declarar modalidad** | — | — | Acceso | — | — |
| **Gestionar avales** | Acceso | — | — | Acceso | Acceso |
| **Aprobar / rechazar** | Acceso | — | — | Acceso | — |
| **Importar padrón** | Acceso | — | — | Acceso | Acceso |
| **Asignar tutor de grado** | Acceso | — | — | Acceso | — |
| **Registrar etapas** | Acceso | — | — | Acceso | Acceso |
| **Asignar jurado** | Acceso | — | — | Acceso | Acceso |
| **Registrar acta** | Acceso | — | — | Acceso | Acceso |
| **Generar documentos** | Acceso | — | — | Acceso | Acceso |
| **Configurar parámetros** | Acceso | — | — | Acceso | — |

---

## 14. Conclusiones de la arquitectura

La arquitectura adoptada permite que el sistema sea **modular, seguro y mantenible**,
garantizando la separación clara entre la lógica de negocio, la presentación y el
acceso a los datos. La aplicación del patrón MVC junto con un front controller único
y una capa de datos basada en consultas preparadas proporciona una base sólida sobre
la cual se construyeron los dos módulos funcionales del sistema.

La decisión de utilizar la arquitectura MVC nativa, sin frameworks externos, favoreció
un mayor comprensión de los mecanismos internos del desarrollo web en PHP, y la
adopción de contenedores garantiza un despliegue reproducible en cualquier entorno.

---

*Fin del documento de Arquitectura*
