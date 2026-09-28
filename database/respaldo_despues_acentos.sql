-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: tutorias_db
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `carreras`
--

DROP TABLE IF EXISTS `carreras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carreras` (
  `id_carrera` int NOT NULL AUTO_INCREMENT,
  `nombre_carrera` varchar(150) NOT NULL,
  PRIMARY KEY (`id_carrera`),
  UNIQUE KEY `uq_carreras_nombre` (`nombre_carrera`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carreras`
--

LOCK TABLES `carreras` WRITE;
/*!40000 ALTER TABLE `carreras` DISABLE KEYS */;
INSERT INTO `carreras` VALUES (4,'Administración de Empresas'),(1,'Ingeniería de Sistemas'),(2,'Ingeniería de Software'),(3,'Ingeniería en Telecomunicaciones');
/*!40000 ALTER TABLE `carreras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disponibilidad_tutor`
--

DROP TABLE IF EXISTS `disponibilidad_tutor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `disponibilidad_tutor` (
  `id_disponibilidad` int NOT NULL AUTO_INCREMENT,
  `id_tutor` int NOT NULL,
  `id_materia` int NOT NULL,
  `id_turno` int NOT NULL,
  PRIMARY KEY (`id_disponibilidad`),
  UNIQUE KEY `uq_disp_tutor_materia_turno` (`id_tutor`,`id_materia`,`id_turno`),
  KEY `fk_disp_materia` (`id_materia`),
  KEY `fk_disp_turno` (`id_turno`),
  CONSTRAINT `fk_disp_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE,
  CONSTRAINT `fk_disp_turno` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id_turno`) ON UPDATE CASCADE,
  CONSTRAINT `fk_disp_tutor` FOREIGN KEY (`id_tutor`) REFERENCES `tutores` (`id_tutor`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disponibilidad_tutor`
--

LOCK TABLES `disponibilidad_tutor` WRITE;
/*!40000 ALTER TABLE `disponibilidad_tutor` DISABLE KEYS */;
INSERT INTO `disponibilidad_tutor` VALUES (1,1,1,3),(3,1,3,2),(2,1,3,3),(4,2,1,3),(5,2,4,3),(6,3,2,2),(7,3,5,3),(8,4,3,2),(9,4,8,2),(10,5,6,3),(11,5,10,3),(12,6,7,2),(13,6,12,2),(14,7,8,2),(15,7,9,2),(16,8,10,2),(17,8,11,3),(19,9,12,2),(18,9,12,3),(20,10,3,2),(21,10,9,2),(22,11,4,2),(23,11,11,3);
/*!40000 ALTER TABLE `disponibilidad_tutor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estudiantes`
--

DROP TABLE IF EXISTS `estudiantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estudiantes` (
  `id_estudiante` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_carrera` int NOT NULL,
  `semestre` tinyint NOT NULL,
  `registro_universitario` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id_estudiante`),
  UNIQUE KEY `id_usuario` (`id_usuario`),
  UNIQUE KEY `registro_universitario` (`registro_universitario`),
  KEY `fk_estudiantes_carreras` (`id_carrera`),
  CONSTRAINT `fk_estudiantes_carreras` FOREIGN KEY (`id_carrera`) REFERENCES `carreras` (`id_carrera`) ON UPDATE CASCADE,
  CONSTRAINT `fk_estudiantes_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estudiantes`
--

LOCK TABLES `estudiantes` WRITE;
/*!40000 ALTER TABLE `estudiantes` DISABLE KEYS */;
INSERT INTO `estudiantes` VALUES (1,3,1,4,'RU-2026-98765'),(2,14,1,1,'RU-2026-00001'),(3,15,1,2,'RU-2026-00002'),(4,16,1,3,'RU-2026-00003'),(5,17,1,4,'RU-2026-00004'),(6,18,1,5,'RU-2026-00005'),(7,19,1,6,'RU-2026-00006'),(8,20,1,7,'RU-2026-00007'),(9,21,1,8,'RU-2026-00008'),(10,22,1,1,'RU-2026-00009'),(11,23,1,2,'RU-2026-00010'),(12,24,1,3,'RU-2026-00011'),(13,25,1,4,'RU-2026-00012'),(14,26,1,5,'RU-2026-00013'),(15,27,2,6,'RU-2026-00014'),(16,28,2,7,'RU-2026-00015'),(17,29,2,8,'RU-2026-00016'),(18,30,2,1,'RU-2026-00017'),(19,31,2,2,'RU-2026-00018'),(20,32,2,3,'RU-2026-00019'),(21,33,2,4,'RU-2026-00020'),(22,34,2,5,'RU-2026-00021'),(23,35,2,6,'RU-2026-00022'),(24,36,2,7,'RU-2026-00023'),(25,37,2,8,'RU-2026-00024'),(26,38,2,1,'RU-2026-00025'),(27,39,2,2,'RU-2026-00026'),(28,40,3,3,'RU-2026-00027'),(29,41,3,4,'RU-2026-00028'),(30,42,3,5,'RU-2026-00029'),(31,43,3,6,'RU-2026-00030'),(32,44,3,7,'RU-2026-00031'),(33,45,3,8,'RU-2026-00032'),(34,46,3,1,'RU-2026-00033'),(35,47,3,2,'RU-2026-00034'),(36,48,3,3,'RU-2026-00035'),(37,49,3,4,'RU-2026-00036'),(38,50,3,5,'RU-2026-00037'),(39,51,3,6,'RU-2026-00038'),(40,52,3,7,'RU-2026-00039'),(41,53,4,8,'RU-2026-00040'),(42,54,4,1,'RU-2026-00041'),(43,55,4,2,'RU-2026-00042'),(44,56,4,3,'RU-2026-00043'),(45,57,4,4,'RU-2026-00044'),(46,58,4,5,'RU-2026-00045'),(47,59,4,6,'RU-2026-00046'),(48,60,4,7,'RU-2026-00047'),(49,61,4,8,'RU-2026-00048'),(50,62,4,1,'RU-2026-00049'),(51,63,4,2,'RU-2026-00050');
/*!40000 ALTER TABLE `estudiantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluaciones_tutoria`
--

DROP TABLE IF EXISTS `evaluaciones_tutoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluaciones_tutoria` (
  `id_evaluacion` int NOT NULL AUTO_INCREMENT,
  `id_tutoria` int NOT NULL,
  `calificacion` tinyint NOT NULL,
  `comentario` text,
  `fecha_evaluacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evaluacion`),
  UNIQUE KEY `id_tutoria` (`id_tutoria`),
  CONSTRAINT `fk_evaluaciones_tutoria` FOREIGN KEY (`id_tutoria`) REFERENCES `tutorias` (`id_tutoria`) ON DELETE CASCADE,
  CONSTRAINT `evaluaciones_tutoria_chk_1` CHECK ((`calificacion` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluaciones_tutoria`
--

LOCK TABLES `evaluaciones_tutoria` WRITE;
/*!40000 ALTER TABLE `evaluaciones_tutoria` DISABLE KEYS */;
/*!40000 ALTER TABLE `evaluaciones_tutoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materias`
--

DROP TABLE IF EXISTS `materias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materias` (
  `id_materia` int NOT NULL AUTO_INCREMENT,
  `nombre_materia` varchar(150) NOT NULL,
  `id_carrera` int DEFAULT NULL,
  PRIMARY KEY (`id_materia`),
  KEY `idx_materias_nombre` (`nombre_materia`),
  KEY `fk_materias_carreras` (`id_carrera`),
  CONSTRAINT `fk_materias_carreras` FOREIGN KEY (`id_carrera`) REFERENCES `carreras` (`id_carrera`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materias`
--

LOCK TABLES `materias` WRITE;
/*!40000 ALTER TABLE `materias` DISABLE KEYS */;
INSERT INTO `materias` VALUES (1,'Base de Datos I',1),(2,'Programación I',1),(3,'Tecnología Web I',1),(4,'Base de Datos II',1),(5,'Programación II',1),(6,'Redes de Computadoras',1),(7,'Estadística I',1),(8,'Ingeniería de Software',2),(9,'Diseño de Interfaces',2),(10,'Telecomunicaciones I',3),(11,'Redes II',3),(12,'Matemáticas Aplicadas',4);
/*!40000 ALTER TABLE `materias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oferta_respuesta`
--

DROP TABLE IF EXISTS `oferta_respuesta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oferta_respuesta` (
  `id_respuesta` int NOT NULL AUTO_INCREMENT,
  `id_oferta` int NOT NULL,
  `id_tutor` int NOT NULL,
  `estado` enum('aceptada','rechazada') NOT NULL,
  `fecha_respuesta` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_respuesta`),
  UNIQUE KEY `uq_oferta_tutor` (`id_oferta`,`id_tutor`),
  KEY `fk_respuesta_tutor` (`id_tutor`),
  CONSTRAINT `fk_respuesta_oferta` FOREIGN KEY (`id_oferta`) REFERENCES `ofertas_admin` (`id_oferta`) ON DELETE CASCADE,
  CONSTRAINT `fk_respuesta_tutor` FOREIGN KEY (`id_tutor`) REFERENCES `tutores` (`id_tutor`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oferta_respuesta`
--

LOCK TABLES `oferta_respuesta` WRITE;
/*!40000 ALTER TABLE `oferta_respuesta` DISABLE KEYS */;
/*!40000 ALTER TABLE `oferta_respuesta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ofertas_admin`
--

DROP TABLE IF EXISTS `ofertas_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ofertas_admin` (
  `id_oferta` int NOT NULL AUTO_INCREMENT,
  `id_materia` int NOT NULL,
  `nivel_academico` varchar(80) NOT NULL DEFAULT 'pregrado',
  `modalidad` enum('presencial','virtual') NOT NULL DEFAULT 'presencial',
  `lugar_o_enlace` varchar(200) DEFAULT NULL,
  `id_turno` int NOT NULL,
  `id_tutor_assigned` int DEFAULT NULL,
  `estado` enum('abierta','asignada','cerrada') NOT NULL DEFAULT 'abierta',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_oferta`),
  UNIQUE KEY `uq_oferta_materia_nivel_turno` (`id_materia`,`nivel_academico`,`id_turno`),
  KEY `fk_oferta_turno` (`id_turno`),
  KEY `fk_oferta_tutor` (`id_tutor_assigned`),
  CONSTRAINT `fk_oferta_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE,
  CONSTRAINT `fk_oferta_turno` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id_turno`) ON UPDATE CASCADE,
  CONSTRAINT `fk_oferta_tutor` FOREIGN KEY (`id_tutor_assigned`) REFERENCES `tutores` (`id_tutor`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ofertas_admin`
--

LOCK TABLES `ofertas_admin` WRITE;
/*!40000 ALTER TABLE `ofertas_admin` DISABLE KEYS */;
/*!40000 ALTER TABLE `ofertas_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registro_accesos`
--

DROP TABLE IF EXISTS `registro_accesos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registro_accesos` (
  `id_acceso` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int DEFAULT NULL,
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_origen` varchar(45) DEFAULT NULL,
  `resultado` enum('exitoso','fallido') NOT NULL,
  PRIMARY KEY (`id_acceso`),
  KEY `fk_accesos_usuarios` (`id_usuario`),
  CONSTRAINT `fk_accesos_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registro_accesos`
--

LOCK TABLES `registro_accesos` WRITE;
/*!40000 ALTER TABLE `registro_accesos` DISABLE KEYS */;
INSERT INTO `registro_accesos` VALUES (1,1,'2026-09-26 12:31:06','172.18.0.1','exitoso'),(2,1,'2026-09-26 12:54:09','172.18.0.1','exitoso'),(3,1,'2026-09-26 12:57:25','172.18.0.1','exitoso'),(4,1,'2026-09-26 13:09:27','172.18.0.1','exitoso');
/*!40000 ALTER TABLE `registro_accesos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(30) NOT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'administrador'),(3,'estudiante'),(2,'tutor');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turnos`
--

DROP TABLE IF EXISTS `turnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turnos` (
  `id_turno` int NOT NULL AUTO_INCREMENT,
  `nombre_turno` varchar(30) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  PRIMARY KEY (`id_turno`),
  UNIQUE KEY `nombre_turno` (`nombre_turno`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turnos`
--

LOCK TABLES `turnos` WRITE;
/*!40000 ALTER TABLE `turnos` DISABLE KEYS */;
INSERT INTO `turnos` VALUES (1,'Mañana','07:30:00','10:30:00'),(2,'Mediodía','11:00:00','14:00:00'),(3,'Tarde','15:00:00','18:00:00'),(4,'Noche','19:00:00','22:00:00');
/*!40000 ALTER TABLE `turnos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tutor_materia`
--

DROP TABLE IF EXISTS `tutor_materia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tutor_materia` (
  `id_tutor` int NOT NULL,
  `id_materia` int NOT NULL,
  PRIMARY KEY (`id_tutor`,`id_materia`),
  KEY `fk_tm_materia` (`id_materia`),
  CONSTRAINT `fk_tm_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE,
  CONSTRAINT `fk_tm_tutor` FOREIGN KEY (`id_tutor`) REFERENCES `tutores` (`id_tutor`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tutor_materia`
--

LOCK TABLES `tutor_materia` WRITE;
/*!40000 ALTER TABLE `tutor_materia` DISABLE KEYS */;
INSERT INTO `tutor_materia` VALUES (1,1),(2,1),(3,2),(1,3),(4,3),(10,3),(2,4),(11,4),(3,5),(5,6),(6,7),(4,8),(7,8),(7,9),(10,9),(5,10),(8,10),(8,11),(11,11),(6,12),(9,12);
/*!40000 ALTER TABLE `tutor_materia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tutores`
--

DROP TABLE IF EXISTS `tutores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tutores` (
  `id_tutor` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `especialidad` varchar(150) DEFAULT NULL,
  `biografia` text,
  PRIMARY KEY (`id_tutor`),
  UNIQUE KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_tutores_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tutores`
--

LOCK TABLES `tutores` WRITE;
/*!40000 ALTER TABLE `tutores` DISABLE KEYS */;
INSERT INTO `tutores` VALUES (1,2,'Desarrollo Web y Bases de Datos','Docente tutor especializado en desarrollo backend y arquitecturas web.'),(2,4,'Bases de Datos','Ingeniera de Sistemas, docente de base de datos y modelado relacional.'),(3,5,'Programación y Algoritmos','Especialista en lógica de programación, estructuras de datos y programación orientada a objetos.'),(4,6,'Desarrollo Web','Ingeniera web full stack con experiencia en HTML, CSS, JavaScript y frameworks frontend.'),(5,7,'Redes de Computadoras','Certificado en redes, enfocado en fundamentos de networking y telecomunicaciones.'),(6,8,'Estadística y Matemáticas','Profesora de estadística aplicada y matemáticas para ingeniería.'),(7,9,'Ingeniería de Software','Ingeniero de software con enfoque en metodologías ágiles y calidad de software.'),(8,10,'Telecomunicaciones','Ingeniera en telecomunicaciones, redes móviles y transmisión de datos.'),(9,11,'Arquitectura y Sistemas de Información','Profesor universitario de arquitectura empresarial y sistemas de información.'),(10,12,'Programación Web','Desarrolladora frontend, especialista en diseño de interfaces y experiencia de usuario.'),(11,13,'Bases de Datos Avanzadas','Especialista en administración de bases de datos y consultas avanzadas.');
/*!40000 ALTER TABLE `tutores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tutorias`
--

DROP TABLE IF EXISTS `tutorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tutorias` (
  `id_tutoria` int NOT NULL AUTO_INCREMENT,
  `id_estudiante` int NOT NULL,
  `id_tutor` int NOT NULL,
  `id_materia` int NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `modalidad` enum('presencial','virtual') NOT NULL DEFAULT 'presencial',
  `nivel_academico` varchar(80) NOT NULL DEFAULT 'pregrado',
  `lugar_o_enlace` varchar(200) DEFAULT NULL,
  `estado` enum('pendiente','confirmada','en_proceso','realizada','cancelada') NOT NULL DEFAULT 'pendiente',
  `observaciones` text,
  `fecha_solicitud` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tutoria`),
  UNIQUE KEY `uq_tutor_fecha_hora` (`id_tutor`,`fecha`,`hora_inicio`),
  UNIQUE KEY `uq_estud_fecha_hora` (`id_estudiante`,`fecha`,`hora_inicio`),
  KEY `fk_tutorias_materia` (`id_materia`),
  KEY `idx_tutoria_fecha` (`fecha`),
  KEY `idx_tutoria_estado` (`estado`),
  KEY `idx_tutoria_tutor_fecha` (`id_tutor`,`fecha`),
  CONSTRAINT `fk_tutorias_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tutorias_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tutorias_tutor` FOREIGN KEY (`id_tutor`) REFERENCES `tutores` (`id_tutor`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tutorias`
--

LOCK TABLES `tutorias` WRITE;
/*!40000 ALTER TABLE `tutorias` DISABLE KEYS */;
/*!40000 ALTER TABLE `tutorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `id_rol` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `fk_usuarios_roles` (`id_rol`),
  CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,1,'Admin','Sistema','admin@tutorias.local','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000001',NULL,'activo','2026-09-26 12:15:06'),(2,2,'Carlos','Docente','tutor@tutorias.local','tutor1','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000002',NULL,'activo','2026-09-26 12:15:06'),(3,3,'Maria','Estudiante','estudiante@tutorias.local','estudiante1','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000003',NULL,'activo','2026-09-26 12:15:06'),(4,2,'Lucía','Mamani','lucia.mamani@tutorias.local','tutor2','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000004',NULL,'activo','2026-09-26 12:15:06'),(5,2,'Rodrigo','Fernández','rodrigo.fernandez@tutorias.local','tutor3','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000005',NULL,'activo','2026-09-26 12:15:06'),(6,2,'Gabriela','Quispe','gabriela.quispe@tutorias.local','tutor4','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000006',NULL,'activo','2026-09-26 12:15:06'),(7,2,'Diego','Cardozo','diego.cardozo@tutorias.local','tutor5','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000007',NULL,'activo','2026-09-26 12:15:06'),(8,2,'Valeria','Rojas','valeria.rojas@tutorias.local','tutor6','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000008',NULL,'activo','2026-09-26 12:15:06'),(9,2,'Alejandro','Vaca','alejandro.vaca@tutorias.local','tutor7','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000009',NULL,'activo','2026-09-26 12:15:06'),(10,2,'Camila','Torrez','camila.torrez@tutorias.local','tutor8','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000010',NULL,'activo','2026-09-26 12:15:06'),(11,2,'Sebastián','Flores','sebastian.flores@tutorias.local','tutor9','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000011',NULL,'activo','2026-09-26 12:15:06'),(12,2,'Nicole','Vargas','nicole.vargas@tutorias.local','tutor10','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000012',NULL,'activo','2026-09-26 12:15:06'),(13,2,'Andrés','Ledesma','andres.ledesma@tutorias.local','tutor11','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000013',NULL,'activo','2026-09-26 12:15:06'),(14,3,'Javier','Condori','javier.condori@tutorias.local','estudiante2','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000014',NULL,'activo','2026-09-26 12:15:06'),(15,3,'Daniela','Gutiérrez','daniela.gutierrez@tutorias.local','estudiante3','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000015',NULL,'activo','2026-09-26 12:15:06'),(16,3,'Marco','Quiroga','marco.quiroga@tutorias.local','estudiante4','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000016',NULL,'activo','2026-09-26 12:15:06'),(17,3,'Andrea','Salinas','andrea.salinas@tutorias.local','estudiante5','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000017',NULL,'activo','2026-09-26 12:15:06'),(18,3,'Renato','Zambrana','renato.zambrana@tutorias.local','estudiante6','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000018',NULL,'activo','2026-09-26 12:15:06'),(19,3,'Claudia','Orellana','claudia.orellana@tutorias.local','estudiante7','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000019',NULL,'activo','2026-09-26 12:15:06'),(20,3,'Freddy','Coca','freddy.coca@tutorias.local','estudiante8','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000020',NULL,'activo','2026-09-26 12:15:06'),(21,3,'Patricia','Ribera','patricia.ribera@tutorias.local','estudiante9','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000021',NULL,'activo','2026-09-26 12:15:06'),(22,3,'Sergio','Montaño','sergio.montano@tutorias.local','estudiante10','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000022',NULL,'activo','2026-09-26 12:15:06'),(23,3,'Carolina','Arancibia','carolina.arancibia@tutorias.local','estudiante11','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000023',NULL,'activo','2026-09-26 12:15:06'),(24,3,'Milton','Alarcón','milton.alarcon@tutorias.local','estudiante12','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000024',NULL,'activo','2026-09-26 12:15:06'),(25,3,'Evelyn','Paredes','evelyn.paredes@tutorias.local','estudiante13','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000025',NULL,'activo','2026-09-26 12:15:06'),(26,3,'Jorge','Camacho','jorge.camacho@tutorias.local','estudiante14','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000026',NULL,'activo','2026-09-26 12:15:06'),(27,3,'Marisol','Vera','marisol.vera@tutorias.local','estudiante15','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000027',NULL,'activo','2026-09-26 12:15:06'),(28,3,'Pablo','Estrada','pablo.estrada@tutorias.local','estudiante16','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000028',NULL,'activo','2026-09-26 12:15:06'),(29,3,'Katherine','Delgadillo','katherine.delgadillo@tutorias.local','estudiante17','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000029',NULL,'activo','2026-09-26 12:15:06'),(30,3,'Christian','Cabrera','christian.cabrera@tutorias.local','estudiante18','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000030',NULL,'activo','2026-09-26 12:15:06'),(31,3,'Jimena','Ríos','jimena.rios@tutorias.local','estudiante19','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000031',NULL,'activo','2026-09-26 12:15:06'),(32,3,'Omar','Salazar','omar.salazar@tutorias.local','estudiante20','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000032',NULL,'activo','2026-09-26 12:15:06'),(33,3,'Fabiola','Mercado','fabiola.mercado@tutorias.local','estudiante21','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000033',NULL,'activo','2026-09-26 12:15:06'),(34,3,'Henry','Barrientos','henry.barrientos@tutorias.local','estudiante22','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000034',NULL,'activo','2026-09-26 12:15:06'),(35,3,'Adriana','Céspedes','adriana.cespedes@tutorias.local','estudiante23','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000035',NULL,'activo','2026-09-26 12:15:06'),(36,3,'Ramiro','Poma','ramiro.poma@tutorias.local','estudiante24','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000036',NULL,'activo','2026-09-26 12:15:06'),(37,3,'Lucero','Rivero','lucero.rivero@tutorias.local','estudiante25','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000037',NULL,'activo','2026-09-26 12:15:06'),(38,3,'David','Saravia','david.saravia@tutorias.local','estudiante26','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000038',NULL,'activo','2026-09-26 12:15:06'),(39,3,'Karen','Arze','karen.arze@tutorias.local','estudiante27','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000039',NULL,'activo','2026-09-26 12:15:06'),(40,3,'Iván','Gareca','ivan.gareca@tutorias.local','estudiante28','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000040',NULL,'activo','2026-09-26 12:15:06'),(41,3,'Melissa','Cárdenas','melissa.cardenas@tutorias.local','estudiante29','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000041',NULL,'activo','2026-09-26 12:15:06'),(42,3,'Álvaro','Beltrán','alvaro.beltran@tutorias.local','estudiante30','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000042',NULL,'activo','2026-09-26 12:15:06'),(43,3,'Nadia','Suárez','nadia.suarez@tutorias.local','estudiante31','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000043',NULL,'activo','2026-09-26 12:15:06'),(44,3,'Gabriel','Antelo','gabriel.antelo@tutorias.local','estudiante32','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000044',NULL,'activo','2026-09-26 12:15:06'),(45,3,'Rocío','Bautista','rocio.bautista@tutorias.local','estudiante33','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000045',NULL,'activo','2026-09-26 12:15:06'),(46,3,'Eddy','Cruz','eddy.cruz@tutorias.local','estudiante34','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000046',NULL,'activo','2026-09-26 12:15:06'),(47,3,'Nataly','Hurtado','nataly.hurtado@tutorias.local','estudiante35','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000047',NULL,'activo','2026-09-26 12:15:06'),(48,3,'Óscar','Medrano','oscar.medrano@tutorias.local','estudiante36','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000048',NULL,'activo','2026-09-26 12:15:06'),(49,3,'Alejandra','Portugal','alejandra.portugal@tutorias.local','estudiante37','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000049',NULL,'activo','2026-09-26 12:15:06'),(50,3,'Gustavo','Rocha','gustavo.rocha@tutorias.local','estudiante38','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000050',NULL,'activo','2026-09-26 12:15:06'),(51,3,'Bianca','Vidal','bianca.vidal@tutorias.local','estudiante39','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000051',NULL,'activo','2026-09-26 12:15:06'),(52,3,'Raúl','Menacho','raul.menacho@tutorias.local','estudiante40','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000052',NULL,'activo','2026-09-26 12:15:06'),(53,3,'Eliana','Barrios','eliana.barrios@tutorias.local','estudiante41','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000053',NULL,'activo','2026-09-26 12:15:06'),(54,3,'Fernando','Alvis','fernando.alvis@tutorias.local','estudiante42','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000054',NULL,'activo','2026-09-26 12:15:06'),(55,3,'Mayra','Cuéllar','mayra.cuellar@tutorias.local','estudiante43','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000055',NULL,'activo','2026-09-26 12:15:06'),(56,3,'Esteban','Villarroel','esteban.villarroel@tutorias.local','estudiante44','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000056',NULL,'activo','2026-09-26 12:15:06'),(57,3,'Paola','Durán','paola.duran@tutorias.local','estudiante45','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000057',NULL,'activo','2026-09-26 12:15:06'),(58,3,'Wilson','Cabezas','wilson.cabezas@tutorias.local','estudiante46','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000058',NULL,'activo','2026-09-26 12:15:06'),(59,3,'Ximena','Losantos','ximena.losantos@tutorias.local','estudiante47','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000059',NULL,'activo','2026-09-26 12:15:06'),(60,3,'Boris','Mamani','boris.mamani@tutorias.local','estudiante48','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000060',NULL,'activo','2026-09-26 12:15:06'),(61,3,'Hilda','Choque','hilda.choque@tutorias.local','estudiante49','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000061',NULL,'activo','2026-09-26 12:15:06'),(62,3,'Manuel','Ascarrunz','manuel.ascarrunz@tutorias.local','estudiante50','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000062',NULL,'activo','2026-09-26 12:15:06'),(63,3,'Graciela','Núñez','graciela.nunez@tutorias.local','estudiante51','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','70000063',NULL,'activo','2026-09-26 12:15:06');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'tutorias_db'
--

--
-- Dumping routines for database 'tutorias_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-26 13:15:08
