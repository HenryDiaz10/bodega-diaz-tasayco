-- =============================================================
--  bodega.sql · Base de datos del sistema «Doña Rosa»
--  MySQL 8 · Motor InnoDB · Juego de caracteres utf8mb4
-- =============================================================

CREATE DATABASE IF NOT EXISTS bodega
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bodega;

-- Conserva las tildes de los datos al importar el archivo UTF-8.
SET NAMES utf8mb4;

-- -------------------------------------------------------------
--  Tabla: producto
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS producto (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100)    NOT NULL,
    precio_unitario DECIMAL(10,2)   NOT NULL,
    stock           INT UNSIGNED    NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_producto_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  Tabla: venta
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS venta (
    id    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    cliente VARCHAR(100) NOT NULL DEFAULT '',
    fecha DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  Tabla: detalle_venta
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS detalle_venta (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    venta_id        INT UNSIGNED  NOT NULL,
    producto_id     INT UNSIGNED  NOT NULL,
    cantidad        INT UNSIGNED  NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    descuento       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    CONSTRAINT fk_dv_venta    FOREIGN KEY (venta_id)
        REFERENCES venta(id),
    CONSTRAINT fk_dv_producto FOREIGN KEY (producto_id)
        REFERENCES producto(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  Datos de prueba: 6 productos de bodega peruana
--  Atún Florida tiene stock = 0 para cubrir el caso borde de R1
-- -------------------------------------------------------------
INSERT INTO producto (nombre, precio_unitario, stock) VALUES
    ('Arroz Extra x 1 kg',         2.50, 50),
    ('Aceite Vegetal x 1 L',        8.90, 30),
    ('Leche Gloria x 400 g',        4.20, 25),
    ('Azúcar Rubia x 1 kg',         3.80, 40),
    ('Atún Florida x 170 g',        5.50,  0),
    ('Gaseosa Inca Kola x 500 ml', 12.00, 18)
-- Reimportar conserva los precios y el stock de los productos existentes.
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
