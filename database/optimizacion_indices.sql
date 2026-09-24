-- =========================================================
-- OPTIMIZACIÓN DE ÍNDICES (optimizacion_indices.sql)
-- ---------------------------------------------------------
-- Índices de apoyo recomendados tras la auditoría de
-- rendimiento. La mayoría ya existen en la BD instalada:
--   - tutorias.estado            -> idx_tutoria_estado (existe)
--   - tutor_materia.id_materia   -> fk_tm_materia (existe, cubre la columna)
--   - oferta_respuesta           -> ya cubierta por fk_respuesta_tutor/uq_oferta_tutor
-- Solo falta el índice por estado de ofertas_admin, usado en
-- contarPorEstado() y en los filtros WHERE estado='abierta/asignada'.
-- Aplicación: mysql -uroot -proot_password tutorias_db < optimizacion_indices.sql
-- =========================================================
ALTER TABLE ofertas_admin
    ADD INDEX idx_ofertas_estado (estado);