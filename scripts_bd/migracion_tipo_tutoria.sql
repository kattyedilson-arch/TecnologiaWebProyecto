-- =========================================================
-- MIGRACIÓN: TIPOS DE TUTORÍA
-- ---------------------------------------------------------
-- Agrega el campo tipo_tutoria a la tabla tutorias.
-- Valores: academica, nivelacion, taller
-- =========================================================

USE tutorias_db;

-- 1. Agregar columna tipo_tutoria (si no existe)
SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = 'tutorias_db'
                  AND TABLE_NAME = 'tutorias'
                  AND COLUMN_NAME = 'tipo_tutoria');

SET @sql := IF(@existe = 0,
  'ALTER TABLE tutorias ADD COLUMN tipo_tutoria ENUM(\'academica\',\'nivelacion\',\'taller\') NOT NULL DEFAULT \'academica\' AFTER modalidad',
  'SELECT "Columna tipo_tutoria ya existe"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Migración de tipos de tutoría completada exitosamente.' AS resultado;
