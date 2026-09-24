-- =========================================================
-- MIGRACIÓN: ELIMINAR DÍAS DE LA SEMANA (dia_semana)
-- ---------------------------------------------------------
-- Elimina por completo la dimensión "día de la semana" del
-- sistema de gestión de ofertas y tutorías:
--   1. ofertas_admin:          la oferta deja de estar anclada
--      a un día fijo; la materia se ofrece por turno.
--   2. disponibilidad_tutor:   el tutor se asocia a (materia,
--      turno) sin días; el estudiante elige cualquier fecha.
-- Las claves únicas se reconstruyen sin la columna.
-- =========================================================

USE tutorias_db;

-- 0. Dedupe de seguridad antes de crear las nuevas claves únicas
--    (en caso de instalaciones con días repetidos para el mismo par)
DELETE o1
FROM ofertas_admin o1
INNER JOIN ofertas_admin o2
        ON o1.id_materia = o2.id_materia
       AND o1.nivel_academico = o2.nivel_academico
       AND o1.id_turno = o2.id_turno
       AND o1.id_oferta > o2.id_oferta;

DELETE d1
FROM disponibilidad_tutor d1
INNER JOIN disponibilidad_tutor d2
        ON d1.id_tutor = d2.id_tutor
       AND d1.id_materia = d2.id_materia
       AND d1.id_turno = d2.id_turno
       AND d1.id_disponibilidad > d2.id_disponibilidad;

-- 1. ofertas_admin
-- (la clave vieja respalda el FK por id_materia: se crea la nueva
--  ANTES de eliminar la vieja para no romper la restricción)
ALTER TABLE ofertas_admin ADD UNIQUE KEY uq_oferta_materia_nivel_turno (id_materia, nivel_academico, id_turno);
ALTER TABLE ofertas_admin DROP INDEX uq_oferta_materia_dia_turno;
ALTER TABLE ofertas_admin DROP COLUMN dia_semana;

-- 2. disponibilidad_tutor (mismo orden seguro para respetar los FK)
ALTER TABLE disponibilidad_tutor ADD UNIQUE KEY uq_disp_tutor_materia_turno (id_tutor, id_materia, id_turno);
ALTER TABLE disponibilidad_tutor DROP INDEX uq_disp_tutor_dia_turno;
ALTER TABLE disponibilidad_tutor DROP COLUMN dia_semana;

SELECT 'Migración dia_semana completada.' AS resultado;