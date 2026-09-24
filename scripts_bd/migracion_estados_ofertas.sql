-- =========================================================
-- MIGRACIÓN: ESTADO "EN PROCESO" + MODALIDAD/AULA EN OFERTAS
-- ---------------------------------------------------------
-- 1. Añade el estado 'en_proceso' al ENUM de tutorias.
--    Ciclo de vida: pendiente -> confirmada -> en_proceso -> realizada (o cancelada).
-- 2. Añade modalidad (presencial/virtual) y aula (lugar_o_enlace)
--    a ofertas_admin para que el Administrador defina día, modalidad
--    y aula; el estudiante solo elige la materia y el horario
--    preestablecido publicado.
-- =========================================================

USE tutorias_db;

-- 1. Ampliar ENUM estado de tutorias (añadir 'en_proceso')
SET @tipo := (SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA='tutorias_db' AND TABLE_NAME='tutorias' AND COLUMN_NAME='estado');

SET @sql := IF(@tipo IS NULL OR @tipo NOT LIKE '%en_proceso%',
  "ALTER TABLE tutorias MODIFY estado ENUM('pendiente','confirmada','en_proceso','realizada','cancelada') NOT NULL DEFAULT 'pendiente'",
  'SELECT "estado ya incluye en_proceso"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Columna modalidad en ofertas_admin (si no existe)
SET @existe_modalidad := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                          WHERE TABLE_SCHEMA='tutorias_db' AND TABLE_NAME='ofertas_admin' AND COLUMN_NAME='modalidad');

SET @sql2 := IF(@existe_modalidad = 0,
  "ALTER TABLE ofertas_admin ADD COLUMN modalidad ENUM('presencial','virtual') NOT NULL DEFAULT 'presencial' AFTER nivel_academico",
  'SELECT "modalidad ya existe"'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 3. Columna lugar_o_enlace (aula) en ofertas_admin (si no existe)
SET @existe_aula := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA='tutorias_db' AND TABLE_NAME='ofertas_admin' AND COLUMN_NAME='lugar_o_enlace');

SET @sql3 := IF(@existe_aula = 0,
  "ALTER TABLE ofertas_admin ADD COLUMN lugar_o_enlace VARCHAR(200) NULL AFTER modalidad",
  'SELECT "lugar_o_enlace ya existe"'
);
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

SELECT 'Migración estados/ofertas completada exitosamente.' AS resultado;