-- =========================================================
-- MIGRACIÓN 021: SOLICITUDES DE INTERÉS EN MATERIAS SIN DOCENTE
-- ---------------------------------------------------------
-- Una materia puede estar OFRECIDA y aun así no tener docente:
-- el administrador publica la oferta (turno, modalidad, aula) y
-- es el tutor quien la acepta después. Hasta que eso ocurre,
-- ofertas_admin.id_tutor_assigned queda NULL y el estudiante no
-- puede agendar nada, porque tutorias.id_tutor es NOT NULL.
--
-- Esta tabla registra el INTERÉS del estudiante por ese horario:
-- no crea una sesión (no hay docente que la imparta) pero deja
-- constancia de la demanda para que la administración y los
-- tutores vean que la materia tiene público.
--
-- Estados:
--   pendiente  el estudiante registró su interés y espera docente
--   atendida   un docente aceptó la oferta (ya se puede reservar)
--   cancelada  el estudiante la retiró, o la oferta se cerró/eliminó
--
-- Idempotente: CREATE TABLE IF NOT EXISTS. En esta base ya estaba
-- aplicada (el archivo se había perdido y se recuperó), por lo que
-- scripts/migrar.php la reporta como "ya aplicada" y no la reejecuta;
-- se conserva aquí para que un entorno nuevo sea reproducible.
-- La columna id_oferta y la clave única por oferta llegan en la 022.
-- =========================================================

CREATE TABLE IF NOT EXISTS solicitudes_interes (
    id_solicitud    INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante   INT NOT NULL,
    id_materia      INT NOT NULL,
    estado          ENUM('pendiente', 'atendida', 'cancelada') NOT NULL DEFAULT 'pendiente',
    mensaje         TEXT NULL,
    fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta DATETIME NULL,

    CONSTRAINT fk_interes_estudiante
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes (id_estudiante) ON DELETE CASCADE,
    CONSTRAINT fk_interes_materia
        FOREIGN KEY (id_materia)    REFERENCES materias    (id_materia)    ON DELETE CASCADE,

    -- Un estudiante registra un único interés por materia.
    UNIQUE KEY uq_interes_estudiante_materia (id_estudiante, id_materia),
    KEY idx_interes_materia (id_materia, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
