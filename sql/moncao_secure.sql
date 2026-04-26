-- MONCAO SECURE - Database Script
-- Control de Acceso y Fichaje

CREATE DATABASE IF NOT EXISTS moncao_secure CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE moncao_secure;

-- Tabla de Departamentos
CREATE TABLE departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

-- Tabla de Usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    foto_url VARCHAR(255) DEFAULT NULL,
    rol ENUM('superadmin', 'admin', 'empleado') DEFAULT 'empleado',
    departamento_id INT DEFAULT NULL,
    activo BOOLEAN DEFAULT TRUE,
    archivado BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
);

-- Tabla de Proyectos
CREATE TABLE proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    departamento_id INT DEFAULT NULL,
    fecha_inicio DATE DEFAULT NULL,
    fecha_fin DATE DEFAULT NULL,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
);

-- Tabla de Relación Proyecto-Usuario
CREATE TABLE proyecto_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabla de Fichajes
CREATE TABLE fichajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    proyecto_id INT DEFAULT NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME DEFAULT NULL,
    hora_salida TIME DEFAULT NULL,
    tarde BOOLEAN DEFAULT FALSE,
    minutos_retraso INT DEFAULT 0,
    horas_extra DECIMAL(4,2) DEFAULT 0,
    telework BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id)
);

-- Tabla de Horarios
CREATE TABLE horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dia_semana TINYINT COMMENT '1=Lunes, 5=Viernes',
    hora_inicio TIME DEFAULT '09:00:00',
    hora_fin TIME DEFAULT '17:00:00',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabla de Solicitudes
CREATE TABLE solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tipo ENUM('vacaciones', 'baja_temporal', 'baja_voluntaria', 'despido', 'cambio_horario') NOT NULL,
    descripcion TEXT,
    justificante_url VARCHAR(255) DEFAULT NULL,
    estado ENUM('pendiente', 'aprobada', 'denegada') DEFAULT 'pendiente',
    aprobado_por INT DEFAULT NULL,
    fecha_respuesta TIMESTAMP NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (aprobado_por) REFERENCES users(id)
);

-- Tabla de Vacaciones
CREATE TABLE vacaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('pendiente', 'aprobada', 'denegada') DEFAULT 'pendiente',
    aprobado_por INT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (aprobado_por) REFERENCES users(id)
);

-- Tabla de Eventos
CREATE TABLE eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha DATE NOT NULL,
    departamento_id INT DEFAULT NULL,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
);

-- =====================
-- DATOS DE PRUEBA
-- =====================

-- Insertar Departamentos
INSERT INTO departamentos (nombre) VALUES
('Dirección'),
('Recursos Humanos'),
('Contabilidad'),
('Desarrollo'),
('Diseño');

-- Insertar Usuarios (Contraseña: moncao2024 - hash bcrypt)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (nombre, email, password, rol, departamento_id) VALUES
('Admin Principal', 'superadmin@moncao.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 1),
('Jefe RRHH', 'rrhh@moncao.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 2),
('Juan García', 'juan@moncao.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', 4),
('María López', 'maria@moncao.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', 5);

-- Insertar Proyectos
INSERT INTO proyectos (nombre, departamento_id, fecha_inicio, fecha_fin) VALUES
('Rediseño Web Corporativa', 5, '2024-01-01', '2024-06-30'),
('App Interna RRHH', 4, '2024-02-01', '2024-12-31'),
('Auditoría Contable Q1', 3, '2024-01-01', '2024-03-31');

-- Insertar Relaciones Proyecto-Usuario
INSERT INTO proyecto_usuario (proyecto_id, user_id) VALUES (1, 4), (2, 3), (1, 3);

-- Insertar Horarios para usuarios (L-V)
INSERT INTO horarios (user_id, dia_semana, hora_inicio, hora_fin) VALUES
(3, 1, '09:00:00', '17:00:00'),
(3, 2, '09:00:00', '17:00:00'),
(3, 3, '09:00:00', '17:00:00'),
(3, 4, '09:00:00', '17:00:00'),
(3, 5, '09:00:00', '17:00:00'),
(4, 1, '09:00:00', '17:00:00'),
(4, 2, '09:00:00', '17:00:00'),
(4, 3, '09:00:00', '17:00:00'),
(4, 4, '09:00:00', '17:00:00'),
(4, 5, '09:00:00', '17:00:00');

-- Insertar Algunos Fichajes de Ejemplo
INSERT INTO fichajes (user_id, proyecto_id, fecha, hora_entrada, hora_salida, tarde, minutos_retraso, horas_extra) VALUES
(3, 2, '2024-04-21', '08:55:00', '17:10:00', FALSE, 0, 0.17),
(3, 2, '2024-04-22', '09:02:00', '17:05:00', TRUE, 2, 0),
(3, 2, '2024-04-23', '08:58:00', '17:30:00', FALSE, 0, 0.50),
(4, 1, '2024-04-21', '09:00:00', '17:00:00', FALSE, 0, 0),
(4, 1, '2024-04-22', '09:05:00', '17:10:00', TRUE, 5, 0),
(4, 1, '2024-04-23', '08:50:00', '17:20:00', FALSE, 0, 0.33);