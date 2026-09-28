-- =========================================================
-- MIGRACIÓN 002: NOTIFICACIONES DEL SISTEMA (HU-043)
-- ---------------------------------------------------------
-- Almacena avisos por usuario (destinatario): cambios de
-- estado de tutorías, avisos administrativos y eventos del
-- módulo MG. La campanita del header muestra las 5 más
-- recientes con contador de no leídas.
-- Idempotente: CREATE TABLE IF NOT EXISTS.
-- =========================================================

CREATE TABLE IF NOT EXISTS notificaciones (
  id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL COMMENT 'Destinatario de la notificación',
  tipo VARCHAR(30) NOT NULL DEFAULT 'sistema' COMMENT 'tutoria|sistema|modalidad|acta',
  titulo VARCHAR(150) NOT NULL,
  mensaje TEXT,
  enlace VARCHAR(255) NULL COMMENT 'Ruta a la que lleva al hacer clic',
  leida TINYINT(1) NOT NULL DEFAULT 0,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notif_usuario (id_usuario, leida),
  CONSTRAINT fk_notif_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;