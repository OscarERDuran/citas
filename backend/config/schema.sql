-- Schema de Base de Datos para Famicitas
-- Versión: 1.0.0
-- Fecha: 2025-09-22

-- Configuración inicial
SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- Tabla de usuarios (base para todos los tipos de usuario)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `rol` enum('administrador','medico','paciente') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_rol` (`rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de especialidades médicas
CREATE TABLE IF NOT EXISTS `especialidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de médicos
CREATE TABLE IF NOT EXISTS `medicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `cedula` varchar(20) NOT NULL UNIQUE,
  `telefono` varchar(20),
  `especialidad_id` int(11) NOT NULL,
  `numero_licencia` varchar(50),
  `biografia` text,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`) ON DELETE RESTRICT,
  KEY `idx_cedula` (`cedula`),
  KEY `idx_especialidad` (`especialidad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de pacientes
CREATE TABLE IF NOT EXISTS `pacientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `cedula` varchar(20) NOT NULL UNIQUE,
  `telefono` varchar(20),
  `fecha_nacimiento` date,
  `direccion` text,
  `contacto_emergencia` varchar(100),
  `telefono_emergencia` varchar(20),
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  KEY `idx_cedula` (`cedula`),
  KEY `idx_fecha_nacimiento` (`fecha_nacimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de citas
CREATE TABLE IF NOT EXISTS `citas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `fecha_cita` date NOT NULL,
  `hora_cita` time NOT NULL,
  `motivo` text NOT NULL,
  `observaciones` text,
  `estado` enum('programada','confirmada','en_curso','completada','cancelada','no_asistio') DEFAULT 'programada',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE,
  KEY `idx_fecha_cita` (`fecha_cita`),
  KEY `idx_estado` (`estado`),
  KEY `idx_paciente_fecha` (`paciente_id`, `fecha_cita`),
  KEY `idx_medico_fecha` (`medico_id`, `fecha_cita`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de horarios de médicos (opcional, para futuras funcionalidades)
CREATE TABLE IF NOT EXISTS `horarios_medicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medico_id` int(11) NOT NULL,
  `dia_semana` tinyint(1) NOT NULL COMMENT '1=Lunes, 2=Martes, ..., 7=Domingo',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE,
  KEY `idx_medico_dia` (`medico_id`, `dia_semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de sesiones (para manejo de sesiones JWT)
CREATE TABLE IF NOT EXISTS `sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `ip_address` varchar(45),
  `user_agent` text,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` datetime NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_usuario_activo` (`usuario_id`, `activo`),
  KEY `idx_expiracion` (`fecha_expiracion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurar AUTO_INCREMENT inicial
ALTER TABLE `usuarios` AUTO_INCREMENT = 1000;
ALTER TABLE `especialidades` AUTO_INCREMENT = 100;
ALTER TABLE `medicos` AUTO_INCREMENT = 2000;
ALTER TABLE `pacientes` AUTO_INCREMENT = 3000;
ALTER TABLE `citas` AUTO_INCREMENT = 4000;
ALTER TABLE `horarios_medicos` AUTO_INCREMENT = 5000;
ALTER TABLE `sesiones` AUTO_INCREMENT = 6000;

-- Restaurar configuraciones
SET foreign_key_checks = 1;
