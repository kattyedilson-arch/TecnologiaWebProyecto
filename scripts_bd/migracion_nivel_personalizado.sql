-- =========================================================
-- MIGRACIÓN: NIVEL ACADÉMICO PERSONALIZADO
-- ---------------------------------------------------------
-- El formulario de ofertas permite ahora "Otra (Personalizar)".
-- Por eso nivel_academico deja de ser ENUM y pasa a VARCHAR(80)
-- en ofertas_admin y en tutorias (esta última hereda el nivel de
-- la oferta al solicitar; un ENUM rechazaría el texto libre).
-- Los valores existentes (pregrado, posgrado, invierno, verano)
-- se conservan tal cual.
-- =========================================================

USE tutorias_db;

ALTER TABLE ofertas_admin MODIFY nivel_academico VARCHAR(80) NOT NULL DEFAULT 'pregrado';
ALTER TABLE tutorias MODIFY nivel_academico VARCHAR(80) NOT NULL DEFAULT 'pregrado';

SELECT 'Migración a nivel_academico VARCHAR(80) completada.' AS resultado;