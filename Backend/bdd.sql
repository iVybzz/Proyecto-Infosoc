-- Tabla Personal (Usuarios del sistema)
CREATE TABLE personal (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(10) UNIQUE NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    contrasena VARCHAR(255) NOT NULL
);

-- Tabla beneficiario
CREATE TABLE beneficiario (
    id_beneficiario INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(10) UNIQUE NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    telefono VARCHAR(15) NULL,
    direccion VARCHAR(200) NOT NULL,
    fecha_registro DATE
);

-- Tabla Categoria_Activo
CREATE TABLE categoria_activo (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(50) NOT NULL,
    es_retornable BOOLEAN NOT NULL
);

-- Tabla Activo
CREATE TABLE activo (
    id_activo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    estado_fisico VARCHAR(30) NOT NULL,
    disponibilidad VARCHAR(30) NOT NULL,
    cantidad INT NOT NULL,
    id_categoria INT NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES categoria_activo(id_categoria)
);

-- Tabla Transaccion
CREATE TABLE transaccion (
    id_transaccion INT AUTO_INCREMENT PRIMARY KEY,
    fecha_salida DATE NOT NULL,
    fecha_devolucion_esperada DATE NULL,
    fecha_devolucion_real DATE NULL,
    observaciones TEXT NULL,
    cantidad_entregada INT NOT NULL,
    id_activo INT NOT NULL,
    id_beneficiario INT NOT NULL,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_activo) REFERENCES activo(id_activo),
    FOREIGN KEY (id_beneficiario) REFERENCES beneficiario(id_beneficiario),
    FOREIGN KEY (id_usuario) REFERENCES personal(id_usuario)
);