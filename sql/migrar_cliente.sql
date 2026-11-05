-- Solo para una base bodega creada con la versión anterior del esquema.
-- Ejecutar una sola vez; no es necesario en instalaciones nuevas.
USE bodega;
ALTER TABLE venta ADD COLUMN cliente VARCHAR(100) NOT NULL DEFAULT '' AFTER id;
