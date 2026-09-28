-- =========================================================
-- MIGRACIÓN 011: AVALES Y JURADO DE DECLARACIÓN
-- -----------------------------------------------------------
-- Tras la aprobación de una declaración, el coordinador MG
-- configura el proceso de grado:
--   1) avales_declaracion    : checklist de documentos/requisitos
--      que el estudiante debe entregar (empresa, pagos, etc.).
--   2) jurados_declaracion   : tribunal (presidente/titular/
--      suplente) de docentes que evalúa la defensa final.
-- Idempotente con CREATE TABLE IF NOT EXISTS.
-- =========================================================

-- Checklist de avales por declaración aprobada
CREATE TABLE IF NOT EXISTS avales_declaracion (
    id_aval          INT AUTO_INCREMENT PRIMARY KEY,
    id_declaracion   INT NOT NULL,
    nombre           VARCHAR(150) NOT NULL,                -- Qué se debe entregar
    descripcion      VARCHAR(255) NULL,                    -- Aclaraciones o formato
    estado           ENUM('pendiente', 'entregado', 'observado') NOT NULL DEFAULT 'pendiente',
    observacion      VARCHAR(255) NULL,                    -- Observación al marcar entregado/observado
    fecha_entrega    DATETIME NULL,                        -- Cuándo se registró la entrega
    fecha_creacion   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_aval_declaracion
        FOREIGN KEY (id_declaracion) REFERENCES declaraciones_modalidad (id_declaracion)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT uq_aval_declaracion_nombre UNIQUE (id_declaracion, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_aval_estado ON avales_declaracion (id_declaracion, estado);

-- Tribunal de docentes asignado a la declaración
CREATE TABLE IF NOT EXISTS jurados_declaracion (
    id_jurado        INT AUTO_INCREMENT PRIMARY KEY,
    id_declaracion   INT NOT NULL,
    id_usuario       INT NOT NULL,                         -- Docente (usuario rol 'tutor')
    rol_jurado       ENUM('presidente', 'titular', 'suplente') NOT NULL DEFAULT 'titular',
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_jurado_declaracion
        FOREIGN KEY (id_declaracion) REFERENCES declaraciones_modalidad (id_declaracion)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_jurado_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT uq_jurado_declaracion_usuario UNIQUE (id_declaracion, id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_jurado_declaracion ON jurados_declaracion (id_declaracion);