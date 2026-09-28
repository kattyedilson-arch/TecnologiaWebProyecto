-- =========================================================
-- MIGRACIÓN 009: ROLES MG Y CATÁLOGO DE MODALIDADES (HU-021)
-- ---------------------------------------------------------
-- 1) Añade los roles del equipo de Modalidades de Grado:
--      coordinador_mg -> gestiona y aprueba el flujo MG
--      auxiliar_mg    -> operación diaria (avales, evaluación, actas)
-- 2) Crea el catálogo de modalidades (qué puede declarar el
--    estudiante) y lo precarga con las modalidades UPDS típicas.
-- Idempotente: CREATE TABLE IF NOT EXISTS + seeds con
-- INSERT ... AS nuevo ON DUPLICATE KEY UPDATE.
-- =========================================================

-- Roles del equipo de MG
INSERT INTO roles (id_rol, nombre_rol) VALUES
(4, 'coordinador_mg'),
(5, 'auxiliar_mg')
AS nuevo
ON DUPLICATE KEY UPDATE nombre_rol = nuevo.nombre_rol;

-- Usuarios del equipo MG (contraseña: password, igual que el resto de dev)
INSERT INTO usuarios (id_usuario, id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado) VALUES
(64, 4, 'Coordinador', 'MG', 'coordinador@tutorias.local', 'coord_mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000064', 'activo'),
(65, 5, 'Auxiliar', 'MG', 'auxiliar@tutorias.local', 'aux_mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000065', 'activo')
AS nuevo
ON DUPLICATE KEY UPDATE usuario = nuevo.usuario;

-- Catálogo de modalidades de grado ofrecidas
CREATE TABLE IF NOT EXISTS modalidades_catalogo (
  id_modalidad INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL COMMENT 'Clave corta (p. ej. MOD-PG)',
  nombre VARCHAR(120) NOT NULL,
  descripcion TEXT,
  tipo VARCHAR(40) NOT NULL DEFAULT 'documental' COMMENT 'documental|investigacion|practica',
  requisitos TEXT,
  estado ENUM('publicada', 'no_publicada') NOT NULL DEFAULT 'no_publicada' COMMENT 'publicada: la ven los estudiantes',
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_modalidad_codigo (codigo),
  UNIQUE KEY uq_modalidad_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modalidades UPDS precargadas
INSERT INTO modalidades_catalogo (codigo, nombre, descripcion, tipo, requisitos, estado) VALUES
('MOD-PG', 'Proyecto de Grado',
 'Elaboración de un proyecto tecnológico o de ingeniería aplicado a un problema real de la región, con entregables documentales y funcionales.',
 'investigacion',
 'Tener aprobada al menos el 80% de la malla; no arrastrar materias que impidan la inscripción.',
 'publicada'),
('MOD-TG', 'Tesis de Grado',
 'Trabajo de investigación formal con planteamiento de problema, marco teórico, hipótesis y aporte científico.',
 'investigacion',
 'Tener aprobado el taller de investigación; carta de un tutor académico que lo acompañe.',
 'publicada'),
('MOD-TD', 'Trabajo Dirigido',
 'Intervención profesional en una empresa u organismo, bajo supervisión docente, que resuelve un requerimiento institucional.',
 'practica',
 'Convenio vigente de la UPDS con la institución donde se realizará el trabajo.',
 'publicada'),
('MOD-ROC', 'Proyecto de Robótica y Control',
 'Desarrollo de un prototipo funcional de robótica o automatización con memoria técnica asociada.',
 'practica',
 'Disponibilidad del laboratorio de electrónica y aprobación de la Dirección de Carrera.',
 'publicada'),
('MOD-SPE', 'Seminario de Actualización Profesional',
 'Complementación con seminarios y talleres de actualización en su área profesional, con examen integral final.',
 'documental',
 'Acreditar al menos 120 horas en cursos y seminarios UPDS reconocidos por la carrera.',
 'publicada')
AS nuevo
ON DUPLICATE KEY UPDATE nombre = nuevo.nombre;