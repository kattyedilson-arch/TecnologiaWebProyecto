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
  id_materia INT NOT NULL,
  dia_semana ENUM('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado') NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  CONSTRAINT fk_disp_tutor FOREIGN KEY (id_tutor) REFERENCES tutores(id_tutor) ON DELETE CASCADE,
  CONSTRAINT fk_disp_materia FOREIGN KEY (id_materia) REFERENCES materias(id_materia) ON DELETE CASCADE
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
INSERT INTO carreras (id_carrera, nombre_carrera) VALUES (1, 'Ingeniería de Sistemas'), (2, 'Ingeniería de Software'), (3, 'Ingeniería en Telecomunicaciones'), (4, 'Administración de Empresas');
INSERT INTO materias (id_materia, nombre_materia, id_carrera) VALUES
(1, 'Base de Datos I', 1),
(2, 'Programación I', 1),
(3, 'Tecnología Web I', 1),
(4, 'Base de Datos II', 1),
(5, 'Programación II', 1),
(6, 'Redes de Computadoras', 1),
(7, 'Estadística I', 1),
(8, 'Ingeniería de Software', 2),
(9, 'Diseño de Interfaces', 2),
(10, 'Telecomunicaciones I', 3),
(11, 'Redes II', 3),
(12, 'Matemáticas Aplicadas', 4);

-- Admin (admin / password)
INSERT INTO usuarios (id_usuario, id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado) VALUES
(1, 1, 'Admin', 'Sistema', 'admin@tutorias.local', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000001', 'activo');

-- Tutor (tutor1 / password)
INSERT INTO usuarios (id_usuario, id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado) VALUES
(2, 2, 'Carlos', 'Docente', 'tutor@tutorias.local', 'tutor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000002', 'activo');

INSERT INTO tutores (id_tutor, id_usuario, especialidad, biografia) VALUES
(1, 2, 'Desarrollo Web y Bases de Datos', 'Docente tutor especializado en desarrollo backend y arquitecturas web.');

INSERT INTO tutor_materia (id_tutor, id_materia) VALUES (1, 1), (1, 3);
INSERT INTO disponibilidad_tutor (id_disponibilidad, id_tutor, id_materia, dia_semana, hora_inicio, hora_fin) VALUES
(1, 1, 1, 'Lunes', '14:00:00', '18:00:00'),
(2, 1, 3, 'Miercoles', '14:00:00', '18:00:00'),
(3, 1, 3, 'Viernes', '09:00:00', '12:00:00');

-- Estudiante (estudiante1 / password)
INSERT INTO usuarios (id_usuario, id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado) VALUES
(3, 3, 'Maria', 'Estudiante', 'estudiante@tutorias.local', 'estudiante1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000003', 'activo');

INSERT INTO estudiantes (id_estudiante, id_usuario, id_carrera, semestre, registro_universitario) VALUES
(1, 3, 1, 4, 'RU-2026-98765');

-- =========================================================
-- Datos semilla adicionales: 10 tutores y 50 estudiantes
-- Contraseña de los nuevos usuarios: password
-- =========================================================

-- Tutores (usuarios 4-13 / id_tutor 2-11)
INSERT INTO usuarios (id_usuario, id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado) VALUES
(4, 2, 'Lucía', 'Mamani', 'lucia.mamani@tutorias.local', 'tutor2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000004', 'activo'),
(5, 2, 'Rodrigo', 'Fernández', 'rodrigo.fernandez@tutorias.local', 'tutor3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000005', 'activo'),
(6, 2, 'Gabriela', 'Quispe', 'gabriela.quispe@tutorias.local', 'tutor4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000006', 'activo'),
(7, 2, 'Diego', 'Cardozo', 'diego.cardozo@tutorias.local', 'tutor5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000007', 'activo'),
(8, 2, 'Valeria', 'Rojas', 'valeria.rojas@tutorias.local', 'tutor6', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000008', 'activo'),
(9, 2, 'Alejandro', 'Vaca', 'alejandro.vaca@tutorias.local', 'tutor7', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000009', 'activo'),
(10, 2, 'Camila', 'Torrez', 'camila.torrez@tutorias.local', 'tutor8', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000010', 'activo'),
(11, 2, 'Sebastián', 'Flores', 'sebastian.flores@tutorias.local', 'tutor9', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000011', 'activo'),
(12, 2, 'Nicole', 'Vargas', 'nicole.vargas@tutorias.local', 'tutor10', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000012', 'activo'),
(13, 2, 'Andrés', 'Ledesma', 'andres.ledesma@tutorias.local', 'tutor11', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000013', 'activo');

INSERT INTO tutores (id_tutor, id_usuario, especialidad, biografia) VALUES
(2, 4, 'Bases de Datos', 'Ingeniera de Sistemas, docente de base de datos y modelado relacional.'),
(3, 5, 'Programación y Algoritmos', 'Especialista en lógica de programación, estructuras de datos y programación orientada a objetos.'),
(4, 6, 'Desarrollo Web', 'Ingeniera web full stack con experiencia en HTML, CSS, JavaScript y frameworks frontend.'),
(5, 7, 'Redes de Computadoras', 'Certificado en redes, enfocado en fundamentos de networking y telecomunicaciones.'),
(6, 8, 'Estadística y Matemáticas', 'Profesora de estadística aplicada y matemáticas para ingeniería.'),
(7, 9, 'Ingeniería de Software', 'Ingeniero de software con enfoque en metodologías ágiles y calidad de software.'),
(8, 10, 'Telecomunicaciones', 'Ingeniera en telecomunicaciones, redes móviles y transmisión de datos.'),
(9, 11, 'Arquitectura y Sistemas de Información', 'Profesor universitario de arquitectura empresarial y sistemas de información.'),
(10, 12, 'Programación Web', 'Desarrolladora frontend, especialista en diseño de interfaces y experiencia de usuario.'),
(11, 13, 'Bases de Datos Avanzadas', 'Especialista en administración de bases de datos y consultas avanzadas.');

INSERT INTO tutor_materia (id_tutor, id_materia) VALUES
(2, 1), (2, 4),
(3, 2), (3, 5),
(4, 3), (4, 8),
(5, 6), (5, 10),
(6, 7), (6, 12),
(7, 8), (7, 9),
(8, 10), (8, 11),
(9, 12),
(10, 3), (10, 9),
(11, 4), (11, 11);

INSERT INTO disponibilidad_tutor (id_disponibilidad, id_tutor, id_materia, dia_semana, hora_inicio, hora_fin) VALUES
(4, 2, 1, 'Lunes', '14:00:00', '18:00:00'),
(5, 2, 4, 'Miercoles', '14:00:00', '18:00:00'),
(6, 3, 2, 'Martes', '09:00:00', '13:00:00'),
(7, 3, 5, 'Jueves', '15:00:00', '19:00:00'),
(8, 4, 3, 'Lunes', '09:00:00', '13:00:00'),
(9, 4, 8, 'Viernes', '09:00:00', '13:00:00'),
(10, 5, 6, 'Martes', '14:00:00', '18:00:00'),
(11, 5, 10, 'Viernes', '14:00:00', '18:00:00'),
(12, 6, 7, 'Lunes', '09:00:00', '13:00:00'),
(13, 6, 12, 'Jueves', '09:00:00', '13:00:00'),
(14, 7, 8, 'Miercoles', '09:00:00', '13:00:00'),
(15, 7, 9, 'Viernes', '09:00:00', '13:00:00'),
(16, 8, 10, 'Martes', '09:00:00', '13:00:00'),
(17, 8, 11, 'Jueves', '14:00:00', '18:00:00'),
(18, 9, 12, 'Lunes', '14:00:00', '18:00:00'),
(19, 9, 12, 'Miercoles', '09:00:00', '13:00:00'),
(20, 10, 3, 'Viernes', '09:00:00', '13:00:00'),
(21, 10, 9, 'Sabado', '09:00:00', '13:00:00'),
(22, 11, 4, 'Lunes', '09:00:00', '13:00:00'),
(23, 11, 11, 'Martes', '14:00:00', '18:00:00');

-- Estudiantes (usuarios 14-63 / id_estudiante 2-51)
INSERT INTO usuarios (id_usuario, id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado) VALUES
(14, 3, 'Javier', 'Condori', 'javier.condori@tutorias.local', 'estudiante2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000014', 'activo'),
(15, 3, 'Daniela', 'Gutiérrez', 'daniela.gutierrez@tutorias.local', 'estudiante3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000015', 'activo'),
(16, 3, 'Marco', 'Quiroga', 'marco.quiroga@tutorias.local', 'estudiante4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000016', 'activo'),
(17, 3, 'Andrea', 'Salinas', 'andrea.salinas@tutorias.local', 'estudiante5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000017', 'activo'),
(18, 3, 'Renato', 'Zambrana', 'renato.zambrana@tutorias.local', 'estudiante6', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000018', 'activo'),
(19, 3, 'Claudia', 'Orellana', 'claudia.orellana@tutorias.local', 'estudiante7', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000019', 'activo'),
(20, 3, 'Freddy', 'Coca', 'freddy.coca@tutorias.local', 'estudiante8', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000020', 'activo'),
(21, 3, 'Patricia', 'Ribera', 'patricia.ribera@tutorias.local', 'estudiante9', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000021', 'activo'),
(22, 3, 'Sergio', 'Montaño', 'sergio.montano@tutorias.local', 'estudiante10', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000022', 'activo'),
(23, 3, 'Carolina', 'Arancibia', 'carolina.arancibia@tutorias.local', 'estudiante11', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000023', 'activo'),
(24, 3, 'Milton', 'Alarcón', 'milton.alarcon@tutorias.local', 'estudiante12', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000024', 'activo'),
(25, 3, 'Evelyn', 'Paredes', 'evelyn.paredes@tutorias.local', 'estudiante13', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000025', 'activo'),
(26, 3, 'Jorge', 'Camacho', 'jorge.camacho@tutorias.local', 'estudiante14', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000026', 'activo'),
(27, 3, 'Marisol', 'Vera', 'marisol.vera@tutorias.local', 'estudiante15', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000027', 'activo'),
(28, 3, 'Pablo', 'Estrada', 'pablo.estrada@tutorias.local', 'estudiante16', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000028', 'activo'),
(29, 3, 'Katherine', 'Delgadillo', 'katherine.delgadillo@tutorias.local', 'estudiante17', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000029', 'activo'),
(30, 3, 'Christian', 'Cabrera', 'christian.cabrera@tutorias.local', 'estudiante18', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000030', 'activo'),
(31, 3, 'Jimena', 'Ríos', 'jimena.rios@tutorias.local', 'estudiante19', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000031', 'activo'),
(32, 3, 'Omar', 'Salazar', 'omar.salazar@tutorias.local', 'estudiante20', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000032', 'activo'),
(33, 3, 'Fabiola', 'Mercado', 'fabiola.mercado@tutorias.local', 'estudiante21', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000033', 'activo'),
(34, 3, 'Henry', 'Barrientos', 'henry.barrientos@tutorias.local', 'estudiante22', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000034', 'activo'),
(35, 3, 'Adriana', 'Céspedes', 'adriana.cespedes@tutorias.local', 'estudiante23', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000035', 'activo'),
(36, 3, 'Ramiro', 'Poma', 'ramiro.poma@tutorias.local', 'estudiante24', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000036', 'activo'),
(37, 3, 'Lucero', 'Rivero', 'lucero.rivero@tutorias.local', 'estudiante25', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000037', 'activo'),
(38, 3, 'David', 'Saravia', 'david.saravia@tutorias.local', 'estudiante26', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000038', 'activo'),
(39, 3, 'Karen', 'Arze', 'karen.arze@tutorias.local', 'estudiante27', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000039', 'activo'),
(40, 3, 'Iván', 'Gareca', 'ivan.gareca@tutorias.local', 'estudiante28', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000040', 'activo'),
(41, 3, 'Melissa', 'Cárdenas', 'melissa.cardenas@tutorias.local', 'estudiante29', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000041', 'activo'),
(42, 3, 'Álvaro', 'Beltrán', 'alvaro.beltran@tutorias.local', 'estudiante30', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000042', 'activo'),
(43, 3, 'Nadia', 'Suárez', 'nadia.suarez@tutorias.local', 'estudiante31', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000043', 'activo'),
(44, 3, 'Gabriel', 'Antelo', 'gabriel.antelo@tutorias.local', 'estudiante32', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000044', 'activo'),
(45, 3, 'Rocío', 'Bautista', 'rocio.bautista@tutorias.local', 'estudiante33', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000045', 'activo'),
(46, 3, 'Eddy', 'Cruz', 'eddy.cruz@tutorias.local', 'estudiante34', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000046', 'activo'),
(47, 3, 'Nataly', 'Hurtado', 'nataly.hurtado@tutorias.local', 'estudiante35', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000047', 'activo'),
(48, 3, 'Óscar', 'Medrano', 'oscar.medrano@tutorias.local', 'estudiante36', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000048', 'activo'),
(49, 3, 'Alejandra', 'Portugal', 'alejandra.portugal@tutorias.local', 'estudiante37', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000049', 'activo'),
(50, 3, 'Gustavo', 'Rocha', 'gustavo.rocha@tutorias.local', 'estudiante38', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000050', 'activo'),
(51, 3, 'Bianca', 'Vidal', 'bianca.vidal@tutorias.local', 'estudiante39', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000051', 'activo'),
(52, 3, 'Raúl', 'Menacho', 'raul.menacho@tutorias.local', 'estudiante40', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000052', 'activo'),
(53, 3, 'Eliana', 'Barrios', 'eliana.barrios@tutorias.local', 'estudiante41', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000053', 'activo'),
(54, 3, 'Fernando', 'Alvis', 'fernando.alvis@tutorias.local', 'estudiante42', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000054', 'activo'),
(55, 3, 'Mayra', 'Cuéllar', 'mayra.cuellar@tutorias.local', 'estudiante43', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000055', 'activo'),
(56, 3, 'Esteban', 'Villarroel', 'esteban.villarroel@tutorias.local', 'estudiante44', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000056', 'activo'),
(57, 3, 'Paola', 'Duran', 'paola.duran@tutorias.local', 'estudiante45', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000057', 'activo'),
(58, 3, 'Wilson', 'Cabezas', 'wilson.cabezas@tutorias.local', 'estudiante46', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000058', 'activo'),
(59, 3, 'Ximena', 'Losantos', 'ximena.losantos@tutorias.local', 'estudiante47', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000059', 'activo'),
(60, 3, 'Boris', 'Mamani', 'boris.mamani@tutorias.local', 'estudiante48', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000060', 'activo'),
(61, 3, 'Hilda', 'Choque', 'hilda.choque@tutorias.local', 'estudiante49', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000061', 'activo'),
(62, 3, 'Manuel', 'Ascarrunz', 'manuel.ascarrunz@tutorias.local', 'estudiante50', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000062', 'activo'),
(63, 3, 'Graciela', 'Nuñez', 'graciela.nunez@tutorias.local', 'estudiante51', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '70000063', 'activo');

INSERT INTO estudiantes (id_estudiante, id_usuario, id_carrera, semestre, registro_universitario) VALUES
(2, 14, 1, 1, 'RU-2026-00001'),
(3, 15, 1, 2, 'RU-2026-00002'),
(4, 16, 1, 3, 'RU-2026-00003'),
(5, 17, 1, 4, 'RU-2026-00004'),
(6, 18, 1, 5, 'RU-2026-00005'),
(7, 19, 1, 6, 'RU-2026-00006'),
(8, 20, 1, 7, 'RU-2026-00007'),
(9, 21, 1, 8, 'RU-2026-00008'),
(10, 22, 1, 1, 'RU-2026-00009'),
(11, 23, 1, 2, 'RU-2026-00010'),
(12, 24, 1, 3, 'RU-2026-00011'),
(13, 25, 1, 4, 'RU-2026-00012'),
(14, 26, 1, 5, 'RU-2026-00013'),
(15, 27, 2, 6, 'RU-2026-00014'),
(16, 28, 2, 7, 'RU-2026-00015'),
(17, 29, 2, 8, 'RU-2026-00016'),
(18, 30, 2, 1, 'RU-2026-00017'),
(19, 31, 2, 2, 'RU-2026-00018'),
(20, 32, 2, 3, 'RU-2026-00019'),
(21, 33, 2, 4, 'RU-2026-00020'),
(22, 34, 2, 5, 'RU-2026-00021'),
(23, 35, 2, 6, 'RU-2026-00022'),
(24, 36, 2, 7, 'RU-2026-00023'),
(25, 37, 2, 8, 'RU-2026-00024'),
(26, 38, 2, 1, 'RU-2026-00025'),
(27, 39, 2, 2, 'RU-2026-00026'),
(28, 40, 3, 3, 'RU-2026-00027'),
(29, 41, 3, 4, 'RU-2026-00028'),
(30, 42, 3, 5, 'RU-2026-00029'),
(31, 43, 3, 6, 'RU-2026-00030'),
(32, 44, 3, 7, 'RU-2026-00031'),
(33, 45, 3, 8, 'RU-2026-00032'),
(34, 46, 3, 1, 'RU-2026-00033'),
(35, 47, 3, 2, 'RU-2026-00034'),
(36, 48, 3, 3, 'RU-2026-00035'),
(37, 49, 3, 4, 'RU-2026-00036'),
(38, 50, 3, 5, 'RU-2026-00037'),
(39, 51, 3, 6, 'RU-2026-00038'),
(40, 52, 3, 7, 'RU-2026-00039'),
(41, 53, 4, 8, 'RU-2026-00040'),
(42, 54, 4, 1, 'RU-2026-00041'),
(43, 55, 4, 2, 'RU-2026-00042'),
(44, 56, 4, 3, 'RU-2026-00043'),
(45, 57, 4, 4, 'RU-2026-00044'),
(46, 58, 4, 5, 'RU-2026-00045'),
(47, 59, 4, 6, 'RU-2026-00046'),
(48, 60, 4, 7, 'RU-2026-00047'),
(49, 61, 4, 8, 'RU-2026-00048'),
(50, 62, 4, 1, 'RU-2026-00049'),
(51, 63, 4, 2, 'RU-2026-00050');