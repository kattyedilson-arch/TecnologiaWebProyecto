-- 1. Eliminar base vieja y crearla limpia
DROP DATABASE IF EXISTS tutorias_db;
CREATE DATABASE tutorias_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tutorias_db;

-- 2. Roles del sistema
CREATE TABLE roles (
  id_rol INT AUTO_INCREMENT PRIMARY KEY,
  nombre_rol VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Usuarios (con telefono incluido)
CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  id_rol INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  contrasena_hash VARCHAR(255) NOT NULL,
  telefono VARCHAR(20),
  foto_perfil VARCHAR(255) NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_roles FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Carreras
CREATE TABLE carreras (
  id_carrera INT AUTO_INCREMENT PRIMARY KEY,
  nombre_carrera VARCHAR(150) NOT NULL,
  UNIQUE KEY uq_carreras_nombre (nombre_carrera)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Estudiantes
CREATE TABLE estudiantes (
  id_estudiante INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL UNIQUE,
  id_carrera INT NOT NULL,
  semestre TINYINT NOT NULL,
  registro_universitario VARCHAR(30) UNIQUE,
  CONSTRAINT fk_estudiantes_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_estudiantes_carreras FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tutores
CREATE TABLE tutores (
  id_tutor INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL UNIQUE,
  especialidad VARCHAR(150),
  biografia TEXT,
  CONSTRAINT fk_tutores_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Materias
CREATE TABLE materias (
  id_materia INT AUTO_INCREMENT PRIMARY KEY,
  nombre_materia VARCHAR(150) NOT NULL,
  id_carrera INT,
  INDEX idx_materias_nombre (nombre_materia),
  CONSTRAINT fk_materias_carreras FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Relación Tutor - Materia
CREATE TABLE tutor_materia (
  id_tutor INT NOT NULL,
  id_materia INT NOT NULL,
  PRIMARY KEY (id_tutor, id_materia),
  CONSTRAINT fk_tm_tutor FOREIGN KEY (id_tutor) REFERENCES tutores(id_tutor) ON DELETE CASCADE,
  CONSTRAINT fk_tm_materia FOREIGN KEY (id_materia) REFERENCES materias(id_materia) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Disponibilidad horaria
CREATE TABLE disponibilidad_tutor (
  id_disponibilidad INT AUTO_INCREMENT PRIMARY KEY,
  id_tutor INT NOT NULL,
  dia_semana ENUM('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado') NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  CONSTRAINT fk_disp_tutor FOREIGN KEY (id_tutor) REFERENCES tutores(id_tutor) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Tutorías
CREATE TABLE tutorias (
  id_tutoria INT AUTO_INCREMENT PRIMARY KEY,
  id_estudiante INT NOT NULL,
  id_tutor INT NOT NULL,
  id_materia INT NOT NULL,
  fecha DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  modalidad ENUM('presencial','virtual') NOT NULL DEFAULT 'presencial',
  lugar_o_enlace VARCHAR(200),
  estado ENUM('pendiente','confirmada','realizada','cancelada') NOT NULL DEFAULT 'pendiente',
  observaciones TEXT,
  fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tutorias_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON UPDATE CASCADE,
  CONSTRAINT fk_tutorias_tutor FOREIGN KEY (id_tutor) REFERENCES tutores(id_tutor) ON UPDATE CASCADE,
  CONSTRAINT fk_tutorias_materia FOREIGN KEY (id_materia) REFERENCES materias(id_materia) ON UPDATE CASCADE,
  INDEX idx_tutoria_fecha (fecha),
  INDEX idx_tutoria_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Evaluaciones
CREATE TABLE evaluaciones_tutoria (
  id_evaluacion INT AUTO_INCREMENT PRIMARY KEY,
  id_tutoria INT NOT NULL UNIQUE,
  calificacion TINYINT NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
  comentario TEXT,
  fecha_evaluacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_evaluaciones_tutoria FOREIGN KEY (id_tutoria) REFERENCES tutorias(id_tutoria) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Registro de accesos
CREATE TABLE registro_accesos (
  id_acceso INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NULL,
  fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
  ip_origen VARCHAR(45),
  resultado ENUM('exitoso','fallido') NOT NULL,
  CONSTRAINT fk_accesos_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Datos semilla iniciales
-- =========================================================
INSERT INTO roles (id_rol, nombre_rol) VALUES (1, 'administrador'), (2, 'tutor'), (3, 'estudiante');
INSERT INTO carreras (id_carrera, nombre_carrera) VALUES (1, 'Ingeniería de Sistemas');
INSERT INTO materias (id_materia, nombre_materia, id_carrera) VALUES
(1, 'Base de Datos I', 1),
(2, 'Programación I', 1),
(3, 'Tecnología Web I', 1);

-- Admin (admin / password)
INSERT INTO usuarios (id_usuario, id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado) VALUES
(1, 1, 'Admin', 'Sistema', 'admin@tutorias.local', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000001', 'activo');

-- Tutor (tutor1 / password)
INSERT INTO usuarios (id_usuario, id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado) VALUES
(2, 2, 'Carlos', 'Docente', 'tutor@tutorias.local', 'tutor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000002', 'activo');

INSERT INTO tutores (id_tutor, id_usuario, especialidad, biografia) VALUES
(1, 2, 'Desarrollo Web y Bases de Datos', 'Docente tutor especializado en desarrollo backend y arquitecturas web.');

INSERT INTO tutor_materia (id_tutor, id_materia) VALUES (1, 1), (1, 3);
INSERT INTO disponibilidad_tutor (id_disponibilidad, id_tutor, dia_semana, hora_inicio, hora_fin) VALUES
(1, 1, 'Lunes', '14:00:00', '18:00:00'),
(2, 1, 'Miercoles', '14:00:00', '18:00:00'),
(3, 1, 'Viernes', '09:00:00', '12:00:00');

-- Estudiante (estudiante1 / password)
INSERT INTO usuarios (id_usuario, id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado) VALUES
(3, 3, 'Maria', 'Estudiante', 'estudiante@tutorias.local', 'estudiante1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000003', 'activo');

INSERT INTO estudiantes (id_estudiante, id_usuario, id_carrera, semestre, registro_universitario) VALUES
(1, 3, 1, 4, 'RU-2026-98765');