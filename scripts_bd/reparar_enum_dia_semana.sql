-- =========================================================
-- MIGRACIÓN: Reparar ENUM dia_semana mojibake en ofertas_admin
-- y disponibilidad_tutor
-- ---------------------------------------------------------
-- Las definiciones ENUM estaban doble-codificadas
-- (bytes C383C2A9 = "Ã©" en vez de C3A9 = "é"), por lo que
-- insertar 'Miércoles' correcto fallaba con error 1265
-- "Data truncated for column 'dia_semana'" (modo estricto).
--
-- NOTA sobre la colación utf8mb4_0900_ai_ci (insensible a
-- acentos): no se pueden incluir a la vez 'Miercoles' y
-- 'Miércoles' en el mismo ENUM (se consideran duplicados).
-- Los valores mojibake ('MiÃ©rcoles' = 4D69C383C2A9..., 
-- 'SÃ¡bado' = 53C383C2A1...) SÍ son distintos de los
-- acentuados bajo esa colación (verificado: mi_iguala=0).
--
-- Orden correcto (no rompe el ENUM en modo estricto):
--   1) Ampliar el ENUM añadiendo variantes acentuadas a los
--      valores mojibake existentes (todas las transiciones
--      válidas)
--   2) Migrar filas por HEX(dia_semana) para no depender
--      del charset del cliente al escribir literales
--   3) Compactar el ENUM con los valores definitivos
-- =========================================================

-- 1. Ampliar ENUMs temporalmente (mojibake + acentuados)
ALTER TABLE disponibilidad_tutor
  MODIFY dia_semana ENUM('Lunes','Martes','MiÃ©rcoles','Jueves','Viernes','SÃ¡bado','Miércoles','Sábado') NOT NULL;

ALTER TABLE ofertas_admin
  MODIFY dia_semana ENUM('Lunes','Martes','MiÃ©rcoles','Jueves','Viernes','SÃ¡bado','Miércoles','Sábado') NOT NULL;

-- 2. Migrar filas existentes (por bytes exactos)
UPDATE disponibilidad_tutor SET dia_semana = 'Miércoles' WHERE HEX(dia_semana) = '4D69C383C2A972636F6C6573';
UPDATE disponibilidad_tutor SET dia_semana = 'Sábado'    WHERE HEX(dia_semana) = '53C383C2A16261646F';

UPDATE ofertas_admin SET dia_semana = 'Miércoles' WHERE HEX(dia_semana) = '4D69C383C2A972636F6C6573';
UPDATE ofertas_admin SET dia_semana = 'Sábado'    WHERE HEX(dia_semana) = '53C383C2A16261646F';

-- 3. Compactar ENUMs con los valores definitivos
ALTER TABLE disponibilidad_tutor
  MODIFY dia_semana ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL;

ALTER TABLE ofertas_admin
  MODIFY dia_semana ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL;