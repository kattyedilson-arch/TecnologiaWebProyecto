-- =========================================================
-- MIGRACIÓN 012: ACTA DE CALIFICACIÓN DE LA DEFENSA
-- -----------------------------------------------------------
-- El coordinador MG abre un acta por cada declaración aprobada
-- con tribunal asignado. Registra las notas individuales de cada
-- jurado (acta_calificaciones_jurado) y la cabecera resume la
-- nota final (promedio) y el resultado. El acta pasa de 'abierta'
-- (editable) a 'firmada' (bloqueada e imprimible).
-- Idempotente con CREATE TABLE IF NOT EXISTS.
-- =========================================================

-- Cabecera del acta de calificación (una por declaración)
CREATE TABLE IF NOT EXISTS actas_calificacion (
    id_acta            INT AUTO_INCREMENT PRIMARY KEY,
    id_declaracion     INT NOT NULL,
    estado             ENUM('abierta', 'firmada') NOT NULL DEFAULT 'abierta',
    nota_final         DECIMAL(5, 2) NULL,              -- Promedio de notas del jurado
    resultado          ENUM('pendiente', 'aprobado', 'reprobado') NOT NULL DEFAULT 'pendiente',
    fecha_defensa      DATETIME NULL,                   -- Cuándo se realiza la defensa
    lugar              VARCHAR(255) NULL,               -- Salón/aula virtual de la defensa
    observaciones      TEXT NULL,
    id_firmante        INT NULL,                        -- Usuario que firma (coord. MG o admin)
    fecha_firma        DATETIME NULL,
    fecha_creacion     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_acta_declaracion
        FOREIGN KEY (id_declaracion) REFERENCES declaraciones_modalidad (id_declaracion)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_acta_firmante
        FOREIGN KEY (id_firmante) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL,
    -- Máximo un acta por declaración
    CONSTRAINT uq_acta_declaracion UNIQUE (id_declaracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_acta_estado ON actas_calificacion (estado);

-- Nota de cada miembro del jurado para el acta
CREATE TABLE IF NOT EXISTS acta_calificaciones_jurado (
    id_acta    INT NOT NULL,
    id_jurado  INT NOT NULL,
    nota       DECIMAL(5, 2) NOT NULL,                  -- Nota asignada (escala 0-100)
    comentario VARCHAR(255) NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id_acta, id_jurado),
    CONSTRAINT fk_acta_jurado_acta
        FOREIGN KEY (id_acta) REFERENCES actas_calificacion (id_acta)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_acta_jurado_jurado
        FOREIGN KEY (id_jurado) REFERENCES jurados_declaracion (id_jurado)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_acta_jurado_acta ON acta_calificaciones_jurado (id_acta);