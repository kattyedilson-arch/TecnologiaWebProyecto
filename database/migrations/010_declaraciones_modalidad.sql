-- =========================================================
-- MIGRACIÓN 010: DECLARACIÓN DE MODALIDAD DE GRADO
-- -----------------------------------------------------------
-- Registra la elección de modalidad de cada estudiante (HU-030).
-- Agrupa por periodo académico; un estudiante tiene a lo sumo
-- una declaración activa por periodo. Antes de la revisión del
-- coordinador MG pasa por estados de flujo (borrador -> enviada
-- -> en_revision -> aprobada | rechazada | cancelada).
-- =========================================================

CREATE TABLE IF NOT EXISTS declaraciones_modalidad (
    id_declaracion    INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante     INT NOT NULL,
    id_modalidad      INT NOT NULL,
    id_periodo        INT NOT NULL,
    titulo_proyecto   VARCHAR(255) NULL,                  -- Título/tema del trabajo final
    empresa_org       VARCHAR(255) NULL,                  -- Empresa u organización (si aplica)
    tutor_facultativo VARCHAR(255) NULL,                  -- Docente que acompañará el proceso
    estado            ENUM('borrador', 'enviada', 'en_revision', 'aprobada', 'rechazada', 'cancelada')
                      NOT NULL DEFAULT 'borrador',
    observacion       TEXT NULL,                          -- Motivo de rechazo u observación del coordinador
    fecha_enviada     DATETIME NULL,                      -- Cuándo la cerró el estudiante
    fecha_revision    DATETIME NULL,                      -- Cuándo la resolvió el equipo MG
    fecha_creacion    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_declaracion_estudiante
        FOREIGN KEY (id_estudiante) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_declaracion_modalidad
        FOREIGN KEY (id_modalidad) REFERENCES modalidades_catalogo (id_modalidad)
        ON UPDATE CASCADE ON DELETE RESTRICT,             -- no borrar modalidad en uso
    CONSTRAINT fk_declaracion_periodo
        FOREIGN KEY (id_periodo) REFERENCES periodos (id_periodo)
        ON UPDATE CASCADE ON DELETE CASCADE,

    -- Un estudiante solo puede tener UNA declaración activa por periodo
    CONSTRAINT uq_declaracion_estudiante_periodo UNIQUE (id_estudiante, id_periodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice para el filtro por periodo y estado (dashboard MG)
CREATE INDEX idx_declaracion_estado_periodo
    ON declaraciones_modalidad (id_periodo, estado);