-- =========================================================
-- MIGRACIÓN 011: ELIMINAR ÍNDICE ÚNICO EN NOMBRE DE MODALIDAD
-- ---------------------------------------------------------
-- Permite que dos modalidades tengan el mismo nombre pero
-- códigos distintos (objetivos distintos). Solo el código
-- permanece como único.
-- Idempotente: DROP INDEX IF EXISTS (MariaDB 10.6+ / MySQL 8.0+)
-- =========================================================

ALTER TABLE modalidades_catalogo DROP INDEX uq_modalidad_nombre;