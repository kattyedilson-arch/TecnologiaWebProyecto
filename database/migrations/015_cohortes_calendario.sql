-- =========================================================
-- MIGRACIÓN 015: COHORTES Y CALENDARIO DE MG (HU-022)
-- ---------------------------------------------------------
--  1) cohortes_mg: agrupación académica de estudiantes que
--     cursan la modalidad dentro de un periodo (p. ej. la
--     cohorte "2026-1" del periodo abierto 2026-1).
--  2) calendario_mg: hitos del proceso (talleres, informes,
--     defensas, entregas) asociables a una cohorte o globales.
-- Idempotente: CREATE TABLE IF NOT EXISTS + seeds con
-- INSERT ... AS nuevo ON DUPLICATE KEY UPDATE.
-- =========================================================

CREATE TABLE IF NOT EXISTS cohortes_mg (
  id_cohorte INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL COMMENT 'Código corto único (p. ej. C-2026-1)',
  nombre VARCHAR(120) NOT NULL,
  id_periodo INT NULL COMMENT 'Periodo académico al que pertenece',
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  estado ENUM('vigente', 'cerrada') NOT NULL DEFAULT 'vigente' COMMENT 'vigente: recibe movimientos MG',
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cohorte_codigo (codigo),
  UNIQUE KEY uq_cohorte_nombre (nombre),
  CONSTRAINT fk_cohorte_periodo FOREIGN KEY (id_periodo)
    REFERENCES periodos (id_periodo) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS calendario_mg (
  id_evento INT AUTO_INCREMENT PRIMARY KEY,
  id_cohorte INT NULL COMMENT 'NULL: hito global del proceso MG',
  titulo VARCHAR(160) NOT NULL,
  tipo_hito ENUM('taller', 'informe', 'defensa', 'entrega') NOT NULL DEFAULT 'entrega',
  fecha DATE NOT NULL,
  descripcion VARCHAR(255),
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_calendario_cohorte FOREIGN KEY (id_cohorte)
    REFERENCES cohortes_mg (id_cohorte) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seeds de ejemplo (idempotente)
INSERT INTO cohortes_mg (codigo, nombre, id_periodo, fecha_inicio, fecha_fin, estado) VALUES
('C-2026-1', 'Cohorte 2026-1', NULL, '2026-02-09', '2026-12-18', 'vigente')
AS nuevo
ON DUPLICATE KEY UPDATE nombre = nuevo.nombre;

INSERT INTO calendario_mg (id_cohorte, titulo, tipo_hito, fecha, descripcion) VALUES
(NULL, 'Inscripción y entrega de declaraciones', 'entrega', '2026-03-02', 'Cierre de declaraciones del periodo 2026-1'),
(NULL, 'Taller de modalidades de grado', 'taller', '2026-03-16', 'Taller introductorio con los estudiantes inscritos'),
(NULL, 'Primer informe de avance', 'informe', '2026-05-04', 'Control de avance de los expedientes de la cohorte'),
(NULL, 'Defensas de grado 2026-1', 'defensa', '2026-07-06', 'Ventana de defensas del primer semestre'),
(NULL, 'Entrega final de actas', 'entrega', '2026-07-17', 'Cierre de actas al coordinador de MG')
AS nuevo
ON DUPLICATE KEY UPDATE titulo = nuevo.titulo;