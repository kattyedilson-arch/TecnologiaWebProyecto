-- =========================================================
-- MIGRACIÓN 019: SESIONES GRUPALES (1 MATERIA -> 1 TUTOR -> N ESTUDIANTES)
-- ---------------------------------------------------------
-- Antes, una materia ofertada atendía a UN estudiante por fecha y
-- hora, y el sistema los serializaba día a día. El bloqueo era doble:
--
--   uq_tutor_fecha_hora (id_tutor, fecha, hora_inicio)
--       en 'tutorias', que en el esquema impedía literalmente dos
--       filas del mismo tutor a la misma hora;
--   TutoriaModel::proximaFechaDisponible()
--       que saltaba al día siguiente en cuanto el tutor tenía
--       cualquier sesión a esa hora (TutoriaModel.php:276).
--
-- Con esta migración la oferta pasa a ser una SESIÓN con cupo:
--
--   ofertas_admin.cupo
--       cuántos estudiantes caben por sesión. DEFAULT 1 conserva
--       exactamente el comportamiento actual, así que no hace
--       falta ningún backfill: las ofertas existentes siguen
--       siendo individuales hasta que el admin edite el cupo.
--   tutorias.id_oferta
--       a qué sesión pertenece cada fila. El grupo es el conjunto
--       de filas que comparten id_oferta + fecha + hora_inicio.
--       Se modela así a propósito: cada estudiante conserva SU
--       fila, su estado y su evaluación, de modo que
--       evaluaciones_tutoria y todos los reportes por estudiante
--       siguen funcionando sin cambios.
--
-- El índice único que se elimina estaba cubierto por
-- idx_tutoria_tutor_fecha (id_tutor, fecha), que ya existía como
-- índice normal, así que las consultas por tutor y fecha no
-- pierden cobertura.
--
-- uq_estud_fecha_hora (id_estudiante, fecha, hora_inicio) SE
-- MANTIENE: un estudiante no puede agendarse dos sesiones
-- simultáneas, ni en grupo ni individual.
--
-- Idempotente: los ALTER consultan information_schema y se
-- ejecutan por PREPARE/EXECUTE (patrón de la 013 y la 016),
-- porque MySQL 8 no soporta ADD COLUMN ni DROP INDEX IF EXISTS.
-- Compatible con el runner de scripts/migrar.php.
-- =========================================================

-- ---------------------------------------------------------
-- 1) ofertas_admin.cupo: capacidad de la sesión por estudiante
-- ---------------------------------------------------------
SET @col_cupo = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'ofertas_admin'
      AND COLUMN_NAME  = 'cupo'
);
SET @sql_cupo = IF(@col_cupo = 0,
    'ALTER TABLE ofertas_admin
         ADD COLUMN cupo TINYINT UNSIGNED NOT NULL DEFAULT 1
             COMMENT ''Estudiantes por sesion. 1 = tutoria individual (comportamiento original)''',
    'SELECT 1');
PREPARE stmt_cupo FROM @sql_cupo;
EXECUTE stmt_cupo;
DEALLOCATE PREPARE stmt_cupo;

-- ---------------------------------------------------------
-- 2) tutorias.id_oferta: vincula cada fila a su sesión
-- ---------------------------------------------------------
SET @col_id_oferta = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tutorias'
      AND COLUMN_NAME  = 'id_oferta'
);
SET @sql_id_oferta = IF(@col_id_oferta = 0,
    'ALTER TABLE tutorias
         ADD COLUMN id_oferta INT NULL AFTER id_materia,
         ADD CONSTRAINT fk_tutorias_oferta FOREIGN KEY (id_oferta)
             REFERENCES ofertas_admin (id_oferta) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt_id_oferta FROM @sql_id_oferta;
EXECUTE stmt_id_oferta;
DEALLOCATE PREPARE stmt_id_oferta;

-- ---------------------------------------------------------
-- 3) Backfill: enlaza las tutorías ya agendadas con la oferta
--    que les corresponde. La coincidencia es por tutor, materia,
--    nivel académico y hora de inicio (el turno de la oferta).
--    Solo actualiza las filas que aún están sin sesión asignada,
--    de modo que es seguro repetirlo.
-- ---------------------------------------------------------
UPDATE tutorias tu
INNER JOIN ofertas_admin o
        ON o.id_materia       = tu.id_materia
       AND o.nivel_academico = tu.nivel_academico
       AND o.id_tutor_assigned = tu.id_tutor
INNER JOIN turnos t ON t.id_turno = o.id_turno
SET tu.id_oferta = o.id_oferta
WHERE tu.id_oferta IS NULL
  AND t.hora_inicio = tu.hora_inicio;

-- ---------------------------------------------------------
-- 4) Relajar el bloqueo de una sesión por tutor y hora
-- ---------------------------------------------------------
SET @idx_tutor_hora = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tutorias'
      AND INDEX_NAME   = 'uq_tutor_fecha_hora'
);
SET @sql_drop_tutor_hora = IF(@idx_tutor_hora > 0,
    'ALTER TABLE tutorias DROP INDEX uq_tutor_fecha_hora',
    'SELECT 1');
PREPARE stmt_drop_tutor_hora FROM @sql_drop_tutor_hora;
EXECUTE stmt_drop_tutor_hora;
DEALLOCATE PREPARE stmt_drop_tutor_hora;

-- ---------------------------------------------------------
-- 5) Índice de apoyo para contar los inscritos de una sesión
-- ---------------------------------------------------------
SET @idx_sesion = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tutorias'
      AND INDEX_NAME   = 'idx_tutoria_oferta'
);
SET @sql_idx_sesion = IF(@idx_sesion = 0,
    'ALTER TABLE tutorias
         ADD INDEX idx_tutoria_oferta (id_oferta, fecha, hora_inicio)',
    'SELECT 1');
PREPARE stmt_idx_sesion FROM @sql_idx_sesion;
EXECUTE stmt_idx_sesion;
DEALLOCATE PREPARE stmt_idx_sesion;
