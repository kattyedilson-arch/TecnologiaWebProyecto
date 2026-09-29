-- =========================================================
-- MIGRACIÓN 022: LA SOLICITUD DE INTERÉS APUNTA A LA OFERTA
-- ---------------------------------------------------------
-- La 021 identificaba el interés con (id_estudiante, id_materia),
-- lo cual era correcto solo mientras la materia tuviera una única
-- oferta. Pero una materia puede tener VARIAS ofertas a la vez
-- (p. ej. "Ingeniería de Software" por Mañana y por Tarde) y el
-- estudiante se interesa por un horario concreto, no por la
-- materia en abstracto: de qué oferta dependen el turno, la
-- modalidad y el aula.
--
-- Con esta migración:
--   solicitudes_interes.id_oferta
--       la oferta concreta. Es NULL en las filas antiguas, porque
--       se rellena con un backfill determinista (la oferta viva
--       de menor id de esa materia).
--   fk_interes_oferta
--       ON DELETE CASCADE: si la oferta se elimina, el interés
--       desaparece con ella en lugar de quedar huérfano.
--   uq_interes_estudiante_oferta
--       sustituye a uq_interes_estudiante_materia. El estudiante
--       solo puede tener un interés por OFERTA, de modo que puede
--       interesarse por los dos turnos de la misma materia.
--
-- Nota: el nombre de la restricción única de la 021 se elimina
-- porque impediría interessearse por dos ofertas de una misma
-- materia, que es justo lo que este cambio habilita.
--
-- Idempotente: los ALTER consultan information_schema y se
-- ejecutan por PREPARE/EXECUTE (patrón de la 019 grupal),
-- porque MySQL 8 no soporta ADD COLUMN ni DROP INDEX IF EXISTS.
-- Compatible con el runner de scripts/migrar.php.
-- =========================================================

-- ---------------------------------------------------------
-- 1) solicitudes_interes.id_oferta: la oferta concreta
-- ---------------------------------------------------------
SET @col_id_oferta_interes = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'solicitudes_interes'
      AND COLUMN_NAME  = 'id_oferta'
);
SET @sql_int_id_oferta = IF(@col_id_oferta_interes = 0,
    'ALTER TABLE solicitudes_interes
         ADD COLUMN id_oferta INT NULL AFTER id_materia',
    'SELECT 1');
PREPARE stmt_int_id_oferta FROM @sql_int_id_oferta;
EXECUTE stmt_int_id_oferta;
DEALLOCATE PREPARE stmt_int_id_oferta;

-- ---------------------------------------------------------
-- 2) Backfill: cada interés pendiente apunta a la oferta viva
--    de su materia. Se usa MIN(id_oferta) para que el resultado
--    sea determinista y repeatable, y solo toca filas aún sin
--    oferta asignada.
-- ---------------------------------------------------------
UPDATE solicitudes_interes s
SET s.id_oferta = (
        SELECT MIN(o.id_oferta)
        FROM ofertas_admin o
        WHERE o.id_materia = s.id_materia
          AND o.estado IN ('abierta', 'asignada')
    )
WHERE s.id_oferta IS NULL
  AND EXISTS (
        SELECT 1
        FROM ofertas_admin o
        WHERE o.id_materia = s.id_materia
          AND o.estado IN ('abierta', 'asignada')
    );

-- ---------------------------------------------------------
-- 3) FK a la oferta. Si la oferta se elimina, el interés cae con ella.
-- ---------------------------------------------------------
SET @fk_interes_oferta = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA  = DATABASE()
      AND TABLE_NAME    = 'solicitudes_interes'
      AND CONSTRAINT_NAME = 'fk_interes_oferta'
);
SET @sql_fk_interes_oferta = IF(@fk_interes_oferta = 0,
    'ALTER TABLE solicitudes_interes
         ADD CONSTRAINT fk_interes_oferta
             FOREIGN KEY (id_oferta) REFERENCES ofertas_admin (id_oferta) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt_fk_interes_oferta FROM @sql_fk_interes_oferta;
EXECUTE stmt_fk_interes_oferta;
DEALLOCATE PREPARE stmt_fk_interes_oferta;

-- ---------------------------------------------------------
-- 4) Se libera la restricción por materia: ahora manda la oferta
-- ---------------------------------------------------------
SET @uq_interes_materia = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'solicitudes_interes'
      AND INDEX_NAME   = 'uq_interes_estudiante_materia'
);
SET @sql_drop_uq_materia = IF(@uq_interes_materia > 0,
    'ALTER TABLE solicitudes_interes DROP INDEX uq_interes_estudiante_materia',
    'SELECT 1');
PREPARE stmt_drop_uq_materia FROM @sql_drop_uq_materia;
EXECUTE stmt_drop_uq_materia;
DEALLOCATE PREPARE stmt_drop_uq_materia;

-- ---------------------------------------------------------
-- 5) Un interés por estudiante y oferta
-- ---------------------------------------------------------
SET @uq_interes_oferta = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'solicitudes_interes'
      AND INDEX_NAME   = 'uq_interes_estudiante_oferta'
);
SET @sql_uq_interes_oferta = IF(@uq_interes_oferta = 0,
    'ALTER TABLE solicitudes_interes
         ADD UNIQUE KEY uq_interes_estudiante_oferta (id_estudiante, id_oferta)',
    'SELECT 1');
PREPARE stmt_uq_interes_oferta FROM @sql_uq_interes_oferta;
EXECUTE stmt_uq_interes_oferta;
DEALLOCATE PREPARE stmt_uq_interes_oferta;

-- ---------------------------------------------------------
-- 6) Índice para contar los interesados de una oferta
-- ---------------------------------------------------------
SET @idx_interes_oferta = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'solicitudes_interes'
      AND INDEX_NAME   = 'idx_interes_oferta'
);
SET @sql_idx_interes_oferta = IF(@idx_interes_oferta = 0,
    'ALTER TABLE solicitudes_interes
         ADD INDEX idx_interes_oferta (id_oferta, estado)',
    'SELECT 1');
PREPARE stmt_idx_interes_oferta FROM @sql_idx_interes_oferta;
EXECUTE stmt_idx_interes_oferta;
DEALLOCATE PREPARE stmt_idx_interes_oferta;
