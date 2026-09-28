-- =========================================================
-- MIGRACIÓN: Materia por bloque de disponibilidad
-- ---------------------------------------------------------
-- Añade la columna 'id_materia' a la tabla 'disponibilidad_tutor'
-- para vincular cada bloque horario del tutor con la materia
-- que impartirá en ese rango. Así, al solicitar una tutoría, el
-- estudiante solo ve los horarios en los que el tutor imparte
-- la materia elegida.
--
-- Sirve para bases ya creadas (el init.sql solo corre en el
-- primer arranque del volumen MySQL). Aplicar con:
--
--   docker compose exec -T db mysql -u tutorias_user -p12345 tutorias_db < scripts_bd/migracion_disponibilidad_materia.sql
--
-- =========================================================

-- 1. Añadir la columna (NULL temporalmente para migrar los datos existentes)
ALTER TABLE disponibilidad_tutor
  ADD COLUMN id_materia INT NULL AFTER id_tutor;

-- 2. Rellenar cada bloque ya existente con la primera materia que imparte su tutor
UPDATE disponibilidad_tutor dt
INNER JOIN (
  SELECT id_tutor, MIN(id_materia) AS id_materia
  FROM tutor_materia
  GROUP BY id_tutor
) tm ON tm.id_tutor = dt.id_tutor
SET dt.id_materia = tm.id_materia;

-- 3. Pasar la columna a obligatoria y agregar la llave foránea
ALTER TABLE disponibilidad_tutor
  MODIFY id_materia INT NOT NULL,
  ADD CONSTRAINT fk_disp_materia FOREIGN KEY (id_materia) REFERENCES materias(id_materia) ON DELETE CASCADE;