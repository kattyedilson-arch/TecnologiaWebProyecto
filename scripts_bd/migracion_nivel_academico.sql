-- =========================================================
-- MIGRACIÓN: NIVEL ACADÉMICO (reemplaza tipo_tutoria)
-- ---------------------------------------------------------
-- 1. Agrega nivel_academico a tabla tutorias
-- 2. Agrega nivel_academico a tabla ofertas_admin
-- 3. Migra datos si existe tipo_tutoria
-- 4. Elimina columna tipo_tutoria
-- =========================================================

USE tutorias_db;

-- 1. Agregar nivel_academico a tutorias (si no existe)
SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = 'tutorias_db'
                  AND TABLE_NAME = 'tutorias'
                  AND COLUMN_NAME = 'nivel_academico');

SET @sql := IF(@existe = 0,
  'ALTER TABLE tutorias ADD COLUMN nivel_academico ENUM(\'pregrado\',\'posgrado\',\'invierno\',\'verano\') NOT NULL DEFAULT \'pregrado\' AFTER modalidad',
  'SELECT "Columna nivel_academico ya existe en tutorias"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Agregar nivel_academico a ofertas_admin (si no existe)
SET @existe2 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = 'tutorias_db'
                   AND TABLE_NAME = 'ofertas_admin'
                   AND COLUMN_NAME = 'nivel_academico');

SET @sql2 := IF(@existe2 = 0,
  'ALTER TABLE ofertas_admin ADD COLUMN nivel_academico ENUM(\'pregrado\',\'posgrado\',\'invierno\',\'verano\') NOT NULL DEFAULT \'pregrado\' AFTER id_materia',
  'SELECT "Columna nivel_academico ya existe en ofertas_admin"'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 3. Migrar datos de tipo_tutoria → nivel_academico (si existe tipo_tutoria)
SET @existeTipo := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = 'tutorias_db'
                      AND TABLE_NAME = 'tutorias'
                      AND COLUMN_NAME = 'tipo_tutoria');

SET @sqlMigrate := IF(@existeTipo > 0,
  'UPDATE tutorias SET nivel_academico = CASE tipo_tutoria WHEN \'academica\' THEN \'pregrado\' WHEN \'nivelacion\' THEN \'posgrado\' WHEN \'taller\' THEN \'verano\' ELSE \'pregrado\' END',
  'SELECT "No hay tipo_tutoria que migrar"'
);
PREPARE stmtMigrate FROM @sqlMigrate;
EXECUTE stmtMigrate;
DEALLOCATE PREPARE stmtMigrate;

-- 4. Eliminar columna tipo_tutoria (si existe)
SET @sqlDrop := IF(@existeTipo > 0,
  'ALTER TABLE tutorias DROP COLUMN tipo_tutoria',
  'SELECT "Columna tipo_tutoria no existe"'
);
PREPARE stmtDrop FROM @sqlDrop;
EXECUTE stmtDrop;
DEALLOCATE PREPARE stmtDrop;

SELECT 'Migración a nivel_academico completada exitosamente.' AS resultado;
