-- =========================================================
-- MIGRACIÓN 013: ARCHIVO EN AVALES Y PRESIDENTE DEL ACTA
-- -----------------------------------------------------------
--  1) avales_declaracion: cada aval puede llevar un documento
--     digital adjunto (archivo = ruta web, archivo_nombre =
--     nombre original para la descarga).
--  2) actas_calificacion: guarda el nombre del presidente del
--     tribunal que da fe junto a la firma del coordinador.
-- MySQL 8 no soporta ADD COLUMN IF NOT EXISTS: se protegen las
-- columnas consultando information_schema y ejecutando el ALTER
-- por PREPARE/EXECUTE, para que la migración sea re-ejecutable
-- sin romper (compatible con el runner de scripts/migrar.php).
-- =========================================================

SET @col_archivo = (
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'avales_declaracion'
          AND COLUMN_NAME  = 'archivo'
);
SET @sql_archivo = IF(@col_archivo = 0,
    'ALTER TABLE avales_declaracion
         ADD COLUMN archivo VARCHAR(255) NULL AFTER observacion,
         ADD COLUMN archivo_nombre VARCHAR(255) NULL AFTER archivo',
    'SELECT 1');
PREPARE stmt_archivo FROM @sql_archivo;
EXECUTE stmt_archivo;
DEALLOCATE PREPARE stmt_archivo;

SET @col_presidente = (
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'actas_calificacion'
          AND COLUMN_NAME  = 'presidente'
);
SET @sql_presidente = IF(@col_presidente = 0,
    'ALTER TABLE actas_calificacion
         ADD COLUMN presidente VARCHAR(255) NULL AFTER observaciones',
    'SELECT 1');
PREPARE stmt_presidente FROM @sql_presidente;
EXECUTE stmt_presidente;
DEALLOCATE PREPARE stmt_presidente;