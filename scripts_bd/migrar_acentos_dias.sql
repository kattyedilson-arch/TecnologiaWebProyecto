-- =========================================================
-- MIGRACIÓN: Acentos en los días de la semana
-- ---------------------------------------------------------
-- Convierte 'Miercoles' -> 'Miércoles' y 'Sabado' -> 'Sábado'
-- en las columnas ENUM dia_semana de disponibilidad_tutor
-- y ofertas_admin.
--
-- Orden correcto (no rompe el ENUM en modo estricto):
--   1) Ampliar el ENUM incluyendo los valores acentuados
--   2) Actualizar las filas existentes
--   3) Compactar el ENUM a los valores definitivos
-- =========================================================

-- 1. Ampliar ENUMs temporalmente
ALTER TABLE disponibilidad_tutor
  MODIFY dia_semana ENUM('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Miércoles','Sábado') NOT NULL;

ALTER TABLE ofertas_admin
  MODIFY dia_semana ENUM('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Miércoles','Sábado') NOT NULL;

-- 2. Actualizar filas existentes
UPDATE disponibilidad_tutor SET dia_semana = 'Miércoles' WHERE dia_semana = 'Miercoles';
UPDATE disponibilidad_tutor SET dia_semana = 'Sábado'    WHERE dia_semana = 'Sabado';

UPDATE ofertas_admin SET dia_semana = 'Miércoles' WHERE dia_semana = 'Miercoles';
UPDATE ofertas_admin SET dia_semana = 'Sábado'    WHERE dia_semana = 'Sabado';

-- 3. Compactar ENUMs con los valores definitivos
ALTER TABLE disponibilidad_tutor
  MODIFY dia_semana ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL;

ALTER TABLE ofertas_admin
  MODIFY dia_semana ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL;