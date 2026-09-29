-- =========================================================
-- MIGRACIÓN 018: ATRIBUTOS INSTITUCIONALES DE CARRERA
-- ---------------------------------------------------------
-- El formulario de solicitud de tutoría muestra, bajo la carrera,
-- los metadatos MODELO DE ESTUDIO, SISTEMA DE ESTUDIO y TURNO.
-- 'carreras' solo tenía id_carrera y nombre_carrera, así que no había
-- de dónde obtenerlos.
--
-- codigo          -> sufijo del desplegable, p. ej. "320-04"
-- modelo_estudio  -> etiqueta MODELO DE ESTUDIO (p. ej. MEC)
-- sistema_estudio -> determina el botón activo del grupo
--                    SISTEMA DE ESTUDIO (p. ej. PRESENCIAL)
--
-- Los tres son NULLABLE a propósito: las carreras que aún no tengan
-- código asignado simplemente no muestran el sufijo "(...)" en lugar
-- de inventar uno.
-- =========================================================

ALTER TABLE carreras
    ADD COLUMN codigo VARCHAR(20) NULL AFTER nombre_carrera,
    ADD COLUMN modelo_estudio VARCHAR(40) NULL AFTER codigo,
    ADD COLUMN sistema_estudio VARCHAR(40) NULL AFTER modelo_estudio;

-- Modelo de estudio institucional por defecto
UPDATE carreras SET modelo_estudio = 'MEC' WHERE modelo_estudio IS NULL OR modelo_estudio = '';

-- Sistema de estudio: la UPDS imparte presencial; queda editable
UPDATE carreras SET sistema_estudio = 'PRESENCIAL' WHERE sistema_estudio IS NULL OR sistema_estudio = '';

-- Único código informado por la carrera. Los demás quedan en NULL
-- a la espera del dato oficial.
UPDATE carreras SET codigo = '320-04' WHERE nombre_carrera = 'Ingeniería de Sistemas';
