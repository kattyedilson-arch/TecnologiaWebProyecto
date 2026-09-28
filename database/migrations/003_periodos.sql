-- =========================================================
-- MIGRACIÓN 003: PERIODOS ACADÉMICOS (HU-044)
-- ---------------------------------------------------------
-- Calendarios de la UPDS (p. ej. 2026-1, 2026-2) usados por
-- las Modalidades de Grado para encuadrar declaraciones,
-- avales y actas de defensa dentro de una ventana temporal.
-- Idempotente: CREATE TABLE IF NOT EXISTS + seeds con
-- INSERT ... AS nuevo ON DUPLICATE KEY UPDATE.
-- =========================================================

CREATE TABLE IF NOT EXISTS periodos (
  id_periodo INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL COMMENT 'Nombre visible (p. ej. "2026-1")',
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  estado ENUM('abierto', 'cerrado') NOT NULL DEFAULT 'abierto' COMMENT 'abierto: recibe declaraciones',
  descripcion VARCHAR(255),
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_periodo_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Periodos de ejemplo (idempotente: si ya existen no se reinsertan)
INSERT INTO periodos (nombre, fecha_inicio, fecha_fin, estado, descripcion) VALUES
('2025-2', '2025-08-01', '2025-12-20', 'cerrado', 'Segundo semestre académico 2025'),
('2026-1', '2026-02-09', '2026-07-03', 'abierto',  'Primer semestre académico 2026')
AS nuevo
ON DUPLICATE KEY UPDATE nombre = nuevo.nombre;