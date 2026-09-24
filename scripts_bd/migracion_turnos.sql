-- =========================================================
-- MIGRACIÓN: TURNOS FIJOS
-- ---------------------------------------------------------
-- Convierte la disponibilidad libre de tutores (hora_inicio/hora_fin)
-- al sistema de turnos fijos definidos por el administrador.
-- =========================================================

USE tutorias_db;

-- 1. Crear tabla de turnos fijos
CREATE TABLE IF NOT EXISTS turnos (
  id_turno INT AUTO_INCREMENT PRIMARY KEY,
  nombre_turno VARCHAR(30) NOT NULL UNIQUE,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Insertar los 4 turnos fijos
INSERT INTO turnos (id_turno, nombre_turno, hora_inicio, hora_fin) VALUES
(1, 'Mañana', '07:30:00', '10:30:00'),
(2, 'Mediodía', '11:00:00', '14:00:00'),
(3, 'Tarde', '15:00:00', '18:00:00'),
(4, 'Noche', '19:00:00', '22:00:00')
AS nuevo
ON DUPLICATE KEY UPDATE nombre_turno = nuevo.nombre_turno;

-- 3. Mapear disponibilidad existente al turno más cercano
-- Se crea una tabla temporal con el mapeo de horas a turnos
CREATE TEMPORARY TABLE IF NOT EXISTS tmp_mapeo_turno (
  hora_inicio TIME,
  id_turno INT
);

DELETE FROM tmp_mapeo_turno;
INSERT INTO tmp_mapeo_turno (hora_inicio, id_turno) VALUES
('07:00:00', 1), ('07:30:00', 1), ('08:00:00', 1), ('08:30:00', 1), ('09:00:00', 1), ('09:30:00', 1), ('10:00:00', 1),
('11:00:00', 2), ('11:30:00', 2), ('12:00:00', 2), ('12:30:00', 2), ('13:00:00', 2), ('13:30:00', 2),
('15:00:00', 3), ('15:30:00', 3), ('16:00:00', 3), ('16:30:00', 3), ('17:00:00', 3), ('17:30:00', 3),
('19:00:00', 4), ('19:30:00', 4), ('20:00:00', 4), ('20:30:00', 4), ('21:00:00', 4), ('21:30:00', 4);

-- 4. Agregar columna id_turno a disponibilidad_tutor (si no existe)
SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = 'tutorias_db'
                  AND TABLE_NAME = 'disponibilidad_tutor'
                  AND COLUMN_NAME = 'id_turno');

SET @sql := IF(@existe = 0,
  'ALTER TABLE disponibilidad_tutor ADD COLUMN id_turno INT NOT NULL DEFAULT 1 AFTER dia_semana',
  'SELECT "Columna id_turno ya existe"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Actualizar id_turno basado en hora_inicio existente
UPDATE disponibilidad_tutor dt
INNER JOIN tmp_mapeo_turno tmp ON dt.hora_inicio = tmp.hora_inicio
SET dt.id_turno = tmp.id_turno;

-- 6. Eliminar columnas hora_inicio y hora_fin (si existen)
SET @existe_inicio := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                       WHERE TABLE_SCHEMA = 'tutorias_db'
                         AND TABLE_NAME = 'disponibilidad_tutor'
                         AND COLUMN_NAME = 'hora_inicio');

SET @sql2 := IF(@existe_inicio > 0,
  'ALTER TABLE disponibilidad_tutor DROP COLUMN hora_inicio, DROP COLUMN hora_fin',
  'SELECT "Columnas hora_inicio/hora_fin ya eliminadas"'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 7. Agregar foreign key constraint
SET @existe_fk := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                   WHERE TABLE_SCHEMA = 'tutorias_db'
                     AND TABLE_NAME = 'disponibilidad_tutor'
                     AND CONSTRAINT_NAME = 'fk_disp_turno');

SET @sql3 := IF(@existe_fk = 0,
  'ALTER TABLE disponibilidad_tutor ADD CONSTRAINT fk_disp_turno FOREIGN KEY (id_turno) REFERENCES turnos(id_turno) ON UPDATE CASCADE',
  'SELECT "FK fk_disp_turno ya existe"'
);
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- 8. Actualizar unique key
SET @existe_uq := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                   WHERE TABLE_SCHEMA = 'tutorias_db'
                     AND TABLE_NAME = 'disponibilidad_tutor'
                     AND CONSTRAINT_NAME = 'uq_disp_tutor_dia_hora');

SET @sql4 := IF(@existe_uq > 0,
  'ALTER TABLE disponibilidad_tutor DROP INDEX uq_disp_tutor_dia_hora, ADD UNIQUE KEY uq_disp_tutor_dia_turno (id_tutor, dia_semana, id_turno, id_materia)',
  'SELECT "Unique key ya actualizada"'
);
PREPARE stmt4 FROM @sql4;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

-- 9. Limpiar tabla temporal
DROP TEMPORARY TABLE IF EXISTS tmp_mapeo_turno;

SELECT 'Migración de turnos completada exitosamente.' AS resultado;
