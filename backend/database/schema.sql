-- Base de Datos Famicitas - Sistema de Citas Médicas
-- Creación de base de datos y tablas

CREATE DATABASE IF NOT EXISTS famicitas_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE famicitas_db;

-- Tabla de usuarios del sistema
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'recepcionista', 'medico') DEFAULT 'recepcionista',
    activo BOOLEAN DEFAULT TRUE,
    ultimo_acceso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
);

-- Tabla de especialidades médicas
CREATE TABLE especialidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de médicos
CREATE TABLE medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    documento VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    email VARCHAR(255) UNIQUE,
    especialidad_id INT NOT NULL,
    numero_licencia VARCHAR(50),
    horario_inicio TIME DEFAULT '08:00:00',
    horario_fin TIME DEFAULT '18:00:00',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (especialidad_id) REFERENCES especialidades(id) ON DELETE RESTRICT,
    INDEX idx_documento (documento),
    INDEX idx_especialidad (especialidad_id),
    INDEX idx_email (email)
);

-- Tabla de pacientes
CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    tipo_documento ENUM('cedula', 'tarjeta-identidad', 'cedula-extranjeria', 'pasaporte') NOT NULL,
    documento VARCHAR(20) NOT NULL UNIQUE,
    fecha_nacimiento DATE NOT NULL,
    genero ENUM('masculino', 'femenino', 'otro') NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    direccion TEXT NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    eps VARCHAR(100),
    tipo_sangre VARCHAR(5),
    alergias TEXT,
    observaciones_medicas TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_documento (documento),
    INDEX idx_email (email),
    INDEX idx_nombres_apellidos (nombres, apellidos)
);

-- Tabla de citas médicas
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM('programada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio') DEFAULT 'programada',
    motivo_consulta TEXT,
    observaciones TEXT,
    costo DECIMAL(10,2) DEFAULT 0.00,
    usuario_registro_id INT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_registro_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY unique_medico_fecha_hora (medico_id, fecha, hora),
    INDEX idx_paciente (paciente_id),
    INDEX idx_medico (medico_id),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado)
);

-- Tabla de historial médico
CREATE TABLE historial_medico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    cita_id INT,
    medico_id INT NOT NULL,
    fecha_consulta DATE NOT NULL,
    diagnostico TEXT,
    tratamiento TEXT,
    medicamentos TEXT,
    observaciones TEXT,
    proxima_cita DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE SET NULL,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE RESTRICT,
    INDEX idx_paciente (paciente_id),
    INDEX idx_cita (cita_id),
    INDEX idx_fecha (fecha_consulta)
);

-- Tabla de sesiones (para manejo de autenticación)
CREATE TABLE sesiones (
    id VARCHAR(128) PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(500) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_expires (expires_at)
);

-- Insertar datos iniciales

-- Especialidades
INSERT INTO especialidades (nombre, descripcion) VALUES
('Medicina General', 'Atención médica integral y preventiva'),
('Cardiología', 'Especialidad en enfermedades del corazón y sistema circulatorio'),
('Ginecología', 'Especialidad en salud femenina y sistema reproductivo'),
('Psicología', 'Atención en salud mental y bienestar emocional'),
('Dermatología', 'Especialidad en enfermedades de la piel'),
('Odontología', 'Especialidad en salud bucal y dental');

-- Médicos
INSERT INTO medicos (nombres, apellidos, documento, telefono, email, especialidad_id, numero_licencia) VALUES
('Juan Carlos', 'Perez Rodriguez', '12345678', '3001234567', 'juan.perez@famicitas.com', 2, 'MED-001'),
('Camilo Andrés', 'Paez Martinez', '23456789', '3009876543', 'camilo.paez@famicitas.com', 1, 'MED-002'),
('Rita María', 'Lopez Gonzalez', '34567890', '3001122334', 'rita.lopez@famicitas.com', 3, 'MED-003'),
('María José', 'Castro Herrera', '45678901', '3005566778', 'maria.castro@famicitas.com', 4, 'MED-004');

-- Usuario administrador por defecto
INSERT INTO usuarios (email, password, nombre, rol) VALUES
('admin@famicitas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin'),
('recepcion@famicitas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Recepcionista', 'recepcionista');

-- Paciente de ejemplo
INSERT INTO pacientes (nombres, apellidos, tipo_documento, documento, fecha_nacimiento, genero, telefono, email, direccion, ciudad, departamento, eps) VALUES
('María Elena', 'García Rodríguez', 'cedula', '1234567890', '1990-05-15', 'femenino', '3001234567', 'maria.garcia@email.com', 'Calle 123 #45-67', 'Medellín', 'Antioquia', 'EPS Sura');

-- Cita de ejemplo
INSERT INTO citas (paciente_id, medico_id, fecha, hora, estado, motivo_consulta) VALUES
(1, 1, '2025-09-25', '10:00:00', 'confirmada', 'Control médico general');
