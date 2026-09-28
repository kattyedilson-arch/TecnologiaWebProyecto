-- =========================================================
-- MIGRACIÓN 014: PARÁMETROS CONFIGURABLES DE MG (HU-020)
-- ---------------------------------------------------------
-- Centraliza en una tabla las cifras y umbrales del módulo
-- "Modalidades de Grado" para que el coordinador pueda
-- ajustarlos sin tocar código:
--   APROBADO_MIN          -> nota mínima para aprobar (default 51)
--   NOTA_MAX              -> escala máxima de nota (default 100)
--   DIAS_CITACION         -> antelación mínima en días de citación
--   INTERVALO_MIN_DEFENSA -> separación mínima entre defensas (días)
-- Idempotente: CREATE TABLE IF NOT EXISTS + seeds con
-- INSERT ... ON DUPLICATE KEY UPDATE.
-- =========================================================

CREATE TABLE IF NOT EXISTS parametros_mg (
  id_parametro INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(60) NOT NULL COMMENT 'Clave corta única (p. ej. APROBADO_MIN)',
  valor VARCHAR(100) NOT NULL COMMENT 'Valor textual del parámetro',
  descripcion VARCHAR(255) NOT NULL COMMENT 'Qué controla este parámetro',
  es_editable TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0: solo lectura (valor técnico)',
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_parametro_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO parametros_mg (clave, valor, descripcion, es_editable) VALUES
('APROBADO_MIN', '51', 'Nota final mínima para aprobar la defensa (escala 0-100).', 1),
('NOTA_MAX', '100', 'Máximo de la escala de calificación de las actas.', 0),
('DIAS_CITACION', '3', 'Antelación mínima en días para citar una defensa.', 1),
('INTERVALO_MIN_DEFENSA', '2', 'Días de separación mínima entre dos defensas.', 1)
AS nuevo
ON DUPLICATE KEY UPDATE valor = nuevo.valor, descripcion = nuevo.descripcion, es_editable = nuevo.es_editable;