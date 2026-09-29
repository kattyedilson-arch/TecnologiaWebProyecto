-- =========================================================
-- MIGRACIÓN 016: SPRINT 3 MG (HU-023 A HU-027)
-- ---------------------------------------------------------
--  HU-023: importaciones_mg + importaciones_mg_detalle (padrón CSV).
--  HU-024: etapas_expediente (historial MG1/MG2) + estado del
--          expediente ampliado ('reprobado','abandono') + etapa_actual
--          sobre declaraciones_modalidad (el proxy del expediente).
--  HU-025/026: asignaciones_tutor (historial: vigente/finalizada/
--          reemplazada, nunca se borra).
--  HU-027: plantillas_documento + documentos_generados +
--          contadores_documento (correlativo por tipo+año).
-- También agrega modulo modalidades_catalogo.requiere_tutor
-- (RN-MG-01: solo Proyecto/Tesis/Trabajo Dirigido usan Tutor).
-- Idempotente: CREATE TABLE IF NOT EXISTS + ALTER protegidos por
-- information_schema (patrón de la 013) + seeds ON DUPLICATE.
-- =========================================================

-- ---------------------------------------------------------
-- 1) Etapas del expediente (HU-024): historial de MG1 -> MG2.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS etapas_expediente (
  id_etapa INT AUTO_INCREMENT PRIMARY KEY,
  id_declaracion INT NOT NULL,
  etapa ENUM('previa', 'mg1', 'mg2', 'finalizado') NOT NULL DEFAULT 'previa',
  fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_fin DATETIME NULL,
  resultado ENUM('aprobado', 'reprobado', 'abandono', 'retirado', 'activo') NULL,
  observacion VARCHAR(255) NULL,
  registrado_por INT NULL,
  CONSTRAINT fk_etapa_declaracion FOREIGN KEY (id_declaracion)
    REFERENCES declaraciones_modalidad (id_declaracion) ON DELETE CASCADE,
  CONSTRAINT fk_etapa_registrado_por FOREIGN KEY (registrado_por)
    REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 2) Asignaciones de Tutor (HU-025/026): historial inmutable.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS asignaciones_tutor (
  id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
  id_declaracion INT NOT NULL,
  id_tutor INT NOT NULL,
  fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_fin DATETIME NULL,
  estado ENUM('vigente', 'finalizada', 'reemplazada') NOT NULL DEFAULT 'vigente',
  motivo_fin VARCHAR(255) NULL COMMENT 'Motivo de renuncia/cambio',
  referencia_decanatura VARCHAR(100) NULL COMMENT 'Nro. de nota/resolución de Decanatura',
  disponibilidad_consultada TINYINT(1) NOT NULL DEFAULT 0,
  numero_carta VARCHAR(50) NULL COMMENT 'Correlativo de la carta de asignación',
  observaciones VARCHAR(255) NULL,
  registrado_por INT NULL,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_asig_declaracion (id_declaracion),
  INDEX idx_asig_tutor (id_tutor),
  CONSTRAINT fk_asig_declaracion FOREIGN KEY (id_declaracion)
    REFERENCES declaraciones_modalidad (id_declaracion) ON DELETE CASCADE,
  CONSTRAINT fk_asig_tutor FOREIGN KEY (id_tutor)
    REFERENCES tutores (id_tutor),
  CONSTRAINT fk_asig_registrado_por FOREIGN KEY (registrado_por)
    REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 3) Plantillas de documento (HU-027): editable, con {{variables}}.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS plantillas_documento (
  id_plantilla INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(60) NOT NULL UNIQUE,
  nombre VARCHAR(150) NOT NULL,
  cuerpo_html LONGTEXT NOT NULL,
  version INT NOT NULL DEFAULT 1,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  actualizado_por INT NULL,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_plantilla_actualizado_por FOREIGN KEY (actualizado_por)
    REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 4) Documentos generados (HU-027): snapshot para reimpresión fiel.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS documentos_generados (
  id_documento INT AUTO_INCREMENT PRIMARY KEY,
  id_plantilla INT NOT NULL,
  tipo VARCHAR(40) NOT NULL COMMENT 'p. ej. CARTA_ASIGNACION_TUTOR',
  id_declaracion INT NOT NULL,
  destinatario VARCHAR(255) NULL,
  numero_correlativo VARCHAR(50) NULL,
  contenido_snapshot LONGTEXT NOT NULL,
  generado_por INT NULL,
  fecha_generacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_doc_declaracion (id_declaracion),
  CONSTRAINT fk_doc_plantilla FOREIGN KEY (id_plantilla)
    REFERENCES plantillas_documento (id_plantilla),
  CONSTRAINT fk_doc_declaracion FOREIGN KEY (id_declaracion)
    REFERENCES declaraciones_modalidad (id_declaracion) ON DELETE CASCADE,
  CONSTRAINT fk_doc_generado_por FOREIGN KEY (generado_por)
    REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 5) Contadores de correlativo por tipo + año (bloqueo transaccional).
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS contadores_documento (
  tipo VARCHAR(40) NOT NULL,
  anio SMALLINT NOT NULL,
  ultimo_numero INT NOT NULL DEFAULT 0,
  PRIMARY KEY (tipo, anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 6) Importación de padrón (HU-023): cabecera + detalle.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS importaciones_mg (
  id_importacion INT AUTO_INCREMENT PRIMARY KEY,
  nombre_archivo VARCHAR(255) NOT NULL,
  total_filas INT NOT NULL DEFAULT 0,
  filas_ok INT NOT NULL DEFAULT 0,
  filas_error INT NOT NULL DEFAULT 0,
  expedientes_creados INT NOT NULL DEFAULT 0,
  importado_por INT NULL,
  fecha_importacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_imp_importado_por FOREIGN KEY (importado_por)
    REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS importaciones_mg_detalle (
  id_detalle INT AUTO_INCREMENT PRIMARY KEY,
  id_importacion INT NOT NULL,
  fila INT NOT NULL,
  registro_universitario VARCHAR(30) NULL,
  nombre VARCHAR(150) NULL,
  apellido VARCHAR(150) NULL,
  correo VARCHAR(150) NULL,
  modalidad VARCHAR(120) NULL,
  cohorte VARCHAR(120) NULL,
  resultado ENUM('ok', 'advertencia', 'error', 'pendiente_cuenta', 'omitida') NOT NULL,
  mensaje VARCHAR(255) NULL,
  CONSTRAINT fk_imp_detalle_cabecera FOREIGN KEY (id_importacion)
    REFERENCES importaciones_mg (id_importacion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 7) ALTERs protegidos (patrón 013): columnas nuevas sobre
--    el modelo existente.
-- ---------------------------------------------------------

-- 7a) declaraciones_modalidad.id_cohorte (vínculo al catálogo de cohortes)
SET @col_cohorte = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'declaraciones_modalidad'
      AND COLUMN_NAME  = 'id_cohorte'
);
SET @sql_cohorte = IF(@col_cohorte = 0,
    'ALTER TABLE declaraciones_modalidad
         ADD COLUMN id_cohorte INT NULL AFTER id_periodo,
         ADD CONSTRAINT fk_declaracion_cohorte FOREIGN KEY (id_cohorte)
             REFERENCES cohortes_mg (id_cohorte) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt_cohorte FROM @sql_cohorte;
EXECUTE stmt_cohorte;
DEALLOCATE PREPARE stmt_cohorte;

-- 7b) declaraciones_modalidad.etapa_actual (MG1/MG2, espejo para listados)
SET @col_etapa = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'declaraciones_modalidad'
      AND COLUMN_NAME  = 'etapa_actual'
);
SET @sql_etapa = IF(@col_etapa = 0,
    'ALTER TABLE declaraciones_modalidad
         ADD COLUMN etapa_actual ENUM(''previa'', ''mg1'', ''mg2'', ''finalizado'')
             NOT NULL DEFAULT ''previa'' AFTER estado',
    'SELECT 1');
PREPARE stmt_etapa FROM @sql_etapa;
EXECUTE stmt_etapa;
DEALLOCATE PREPARE stmt_etapa;

-- 7c) declaraciones_modalidad.estado: ampliar con 'reprobado' y 'abandono'
    -- (RN-MG-22: estados terminales los registra Coordinación, nunca se
    --  declara automáticamente).
SET @estado_actual = (
    SELECT COLUMN_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'declaraciones_modalidad'
      AND COLUMN_NAME  = 'estado'
);
SET @tiene_abandono = (
    SELECT LOCATE('abandono', @estado_actual)
);
SET @sql_estado = IF(@tiene_abandono = 0,
    'ALTER TABLE declaraciones_modalidad
         MODIFY COLUMN estado ENUM(''borrador'', ''enviada'', ''en_revision'',
             ''aprobada'', ''rechazada'', ''cancelada'', ''reprobado'', ''abandono'')
             NOT NULL DEFAULT ''borrador''',
    'SELECT 1');
PREPARE stmt_estado FROM @sql_estado;
EXECUTE stmt_estado;
DEALLOCATE PREPARE stmt_estado;

-- 7d) modalidades_catalogo.requiere_tutor (RN-MG-01)
SET @col_req_tutor = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'modalidades_catalogo'
      AND COLUMN_NAME  = 'requiere_tutor'
);
SET @sql_req_tutor = IF(@col_req_tutor = 0,
    'ALTER TABLE modalidades_catalogo
         ADD COLUMN requiere_tutor TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''RN-MG-01: solo algunas modalidades usan tutor''',
    'SELECT 1');
PREPARE stmt_req_tutor FROM @sql_req_tutor;
EXECUTE stmt_req_tutor;
DEALLOCATE PREPARE stmt_req_tutor;

-- Modalidades con tutor (RN-MG-01 confirmado): Proyecto, Tesis, Trabajo Dirigido
UPDATE modalidades_catalogo SET requiere_tutor = 1
WHERE codigo IN ('MOD-PG', 'MOD-TG', 'MOD-TD');

-- ---------------------------------------------------------
-- 8) Seeds idempotentes
-- ---------------------------------------------------------

-- Parámetro de carga recomendada por Tutor (RN-MG-08 [CONFIRMADO 2-3])
INSERT INTO parametros_mg (clave, valor, descripcion, es_editable) VALUES
('tutor_carga_recomendada', '3', 'Carga recomendada de estudiantes por tutor (RN-MG-08). Solo advertencia, nunca bloquea.', 1)
AS nuevo
ON DUPLICATE KEY UPDATE valor = nuevo.valor, descripcion = nuevo.descripcion, es_editable = nuevo.es_editable;

-- Plantilla provisional de carta de asignación de Tutor (HU-027)
INSERT INTO plantillas_documento (codigo, nombre, cuerpo_html, version, activa) VALUES
('CARTA_ASIGNACION_TUTOR', 'Carta de asignación de Tutor (provisional)', '
<p>[PLANTILLA PROVISIONAL - reemplazar por el formato oficial de UPDS]</p>
<p>Cochabamba, {{fecha_larga}}</p>
<p>&nbsp;</p>
<p>Señora: {{estudiante_nombre}}<br>Registro: {{registro_universitario}}<br>Carrera: {{carrera}}</p>
<p>&nbsp;</p>
<p>De nuestra consideración:</p>
<p>Tengo a bien comunicarle que, conforme al flujo de Modalidades de Grado (MG1), y con referencia a la nota de Decanatura <strong>{{referencia_decanatura}}</strong>, el docente <strong>{{tutor_nombre}}</strong> ha sido asignado como su Tutor para la modalidad <strong>{{modalidad}}</strong>, cohorte <strong>{{cohorte}}</strong>, tema "<em>{{tema}}</em>".</p>
<p>La disponibilidad del docente fue consultada previamente conforme a Reglamento.</p>
<p>Sin otro particular, saluda atentamente,</p>
<p>&nbsp;</p>
<p><strong>{{responsable_nombre}}</strong><br>Coordinación de Modalidades de Grado</p>
<p style="font-size:.8rem; color:#666;">Carta N° {{numero_carta}} · generada por el sistema el {{fecha_larga}}</p>
', 1, 1)
AS nuevo
ON DUPLICATE KEY UPDATE nombre = nuevo.nombre, cuerpo_html = nuevo.cuerpo_html, version = nuevo.version, activa = nuevo.activa;