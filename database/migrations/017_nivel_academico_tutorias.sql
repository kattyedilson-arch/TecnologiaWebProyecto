-- =========================================================
-- MIGRACIÓN 017: NIVEL ACADÉMICO EN TUTORÍAS Y OFERTAS
-- ---------------------------------------------------------
-- La BD de producción se creó con un esquema anterior en el que
-- `tutorias` (y `ofertas_admin`) no tenían la columna
-- `nivel_academico`. El baseline 001 usa CREATE TABLE IF NOT
-- EXISTS, así que sobre una base ya existente nunca agregó la
-- columna y el panel del administrador revienta con
-- "Unknown column 'tu.nivel_academico' in 'field list'".
--
-- Qué hace:
--  1) Agrega nivel_academico a tutorias (VARCHAR(80), default
--     'pregrado') de forma idempotente.
--  2) Agrega nivel_academico a ofertas_admin si tampoco existe.
--  3) Normaliza a VARCHAR(80) las que sigan siendo ENUM, para que
--     el formulario de ofertas acepte "Otra (Personalizar)".
--  4) Rescata los datos históricos de `tipo_tutoria` si esa
--     columna antigua todavía está en la tabla.
--
-- Idempotente: columnas consultadas en information_schema y
-- ALTER/MODIFY ejecutados por PREPARE/EXECUTE (patrón de la 013).
-- No lleva USE / CREATE DATABASE: el runner conecta con la BD de .env.
-- =========================================================

-- ---------------------------------------------------------
-- 1) tutorias.nivel_academico
-- ---------------------------------------------------------
SET @col_nivel_tut = (
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'tutorias'
          AND COLUMN_NAME  = 'nivel_academico'
);
SET @sql_nivel_tut = IF(@col_nivel_tut = 0,
    'ALTER TABLE tutorias
         ADD COLUMN nivel_academico VARCHAR(80) NOT NULL DEFAULT ''pregrado''
         AFTER modalidad',
    'DO 0');
PREPARE stmt_nivel_tut FROM @sql_nivel_tut;
EXECUTE stmt_nivel_tut;
DEALLOCATE PREPARE stmt_nivel_tut;

-- ---------------------------------------------------------
-- 2) ofertas_admin.nivel_academico
-- ---------------------------------------------------------
SET @col_nivel_oferta = (
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'ofertas_admin'
          AND COLUMN_NAME  = 'nivel_academico'
);
SET @sql_nivel_oferta = IF(@col_nivel_oferta = 0,
    'ALTER TABLE ofertas_admin
         ADD COLUMN nivel_academico VARCHAR(80) NOT NULL DEFAULT ''pregrado''
         AFTER id_materia',
    'DO 0');
PREPARE stmt_nivel_oferta FROM @sql_nivel_oferta;
EXECUTE stmt_nivel_oferta;
DEALLOCATE PREPARE stmt_nivel_oferta;

-- ---------------------------------------------------------
-- 3) Si alguna quedó como ENUM, abrirla a VARCHAR(80)
-- ---------------------------------------------------------
SET @tipo_nivel_tut = (
        SELECT DATA_TYPE FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'tutorias'
          AND COLUMN_NAME  = 'nivel_academico'
);
SET @sql_tipo_nivel_tut = IF(@tipo_nivel_tut = 'enum',
    'ALTER TABLE tutorias
         MODIFY COLUMN nivel_academico VARCHAR(80) NOT NULL DEFAULT ''pregrado''',
    'DO 0');
PREPARE stmt_tipo_nivel_tut FROM @sql_tipo_nivel_tut;
EXECUTE stmt_tipo_nivel_tut;
DEALLOCATE PREPARE stmt_tipo_nivel_tut;

SET @tipo_nivel_oferta = (
        SELECT DATA_TYPE FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'ofertas_admin'
          AND COLUMN_NAME  = 'nivel_academico'
);
SET @sql_tipo_nivel_oferta = IF(@tipo_nivel_oferta = 'enum',
    'ALTER TABLE ofertas_admin
         MODIFY COLUMN nivel_academico VARCHAR(80) NOT NULL DEFAULT ''pregrado''',
    'DO 0');
PREPARE stmt_tipo_nivel_oferta FROM @sql_tipo_nivel_oferta;
EXECUTE stmt_tipo_nivel_oferta;
DEALLOCATE PREPARE stmt_tipo_nivel_oferta;

-- ---------------------------------------------------------
-- 4) Migrar datos de la columna antigua tipo_tutoria
--    (academica -> pregrado, nivelacion -> posgrado, taller -> verano)
-- ---------------------------------------------------------
SET @col_tipo_tutoria = (
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'tutorias'
          AND COLUMN_NAME  = 'tipo_tutoria'
);
SET @sql_migrar_tipo = IF(@col_tipo_tutoria > 0,
    'UPDATE tutorias
        SET nivel_academico = CASE tipo_tutoria
              WHEN ''academica''  THEN ''pregrado''
              WHEN ''nivelacion'' THEN ''posgrado''
              WHEN ''taller''     THEN ''verano''
              ELSE ''pregrado''
            END
      WHERE nivel_academico IS NULL OR nivel_academico = ''''',
    'DO 0');
PREPARE stmt_migrar_tipo FROM @sql_migrar_tipo;
EXECUTE stmt_migrar_tipo;
DEALLOCATE PREPARE stmt_migrar_tipo;
