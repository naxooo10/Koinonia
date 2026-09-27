-- =========================================================
-- KOINONÍA: CONSULTAS SQL DE LA ETAPA 1
-- 3 SELECT, 2 ALTER, 3 INSERT, 2 UPDATE, 2 DELETE y 1 DROP
-- =========================================================

-- SELECT 1: Consultar Casas Iglesias activas.
SELECT
    id_casa,
    nombre_casa,
    direccion
FROM casa_iglesia
WHERE estado = 1
ORDER BY nombre_casa;

-- SELECT 2: Consultar cada casa activa y su líder actual.
SELECT
    c.id_casa,
    c.nombre_casa,
    u.nombre AS nombre_lider
FROM casa_iglesia AS c
JOIN usuario AS u
    ON u.id_usuario = c.id_lider_responsable
WHERE c.estado = 1
ORDER BY c.nombre_casa;

-- SELECT 3: Consultar los asistentes de cada reunión.
SELECT
    r.id_reunion,
    r.fecha,
    r.tema,
    c.nombre_casa,
    u.nombre AS nombre_asistente
FROM reunion AS r
JOIN casa_iglesia AS c
    ON c.id_casa = r.id_casa
JOIN asistencia AS a
    ON a.id_reunion = r.id_reunion
JOIN usuario AS u
    ON u.id_usuario = a.id_usuario
ORDER BY r.fecha, r.id_reunion, u.nombre;

-- PREPARACIÓN: tabla exclusiva para los ejercicios de modificación.
-- Este CREATE no forma parte de las 13 sentencias evaluadas.
CREATE OR REPLACE TABLE evidencia_casas (
    id_evidencia INT AUTO_INCREMENT PRIMARY KEY,
    nombre_casa VARCHAR(100) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1
);

-- ALTER 1: Agregar la columna dirección.
ALTER TABLE evidencia_casas
ADD COLUMN direccion VARCHAR(200) NULL;

-- ALTER 2: Ampliar la longitud del nombre.
ALTER TABLE evidencia_casas
MODIFY COLUMN nombre_casa VARCHAR(150) NOT NULL;

-- INSERT 1
INSERT INTO evidencia_casas (nombre_casa, direccion, estado)
VALUES ('Casa Prueba A', 'Dirección ficticia A', 1);

-- INSERT 2
INSERT INTO evidencia_casas (nombre_casa, direccion, estado)
VALUES ('Casa Prueba B', 'Dirección ficticia B', 1);

-- INSERT 3
INSERT INTO evidencia_casas (nombre_casa, direccion, estado)
VALUES ('Casa Prueba C', 'Dirección ficticia C', 1);

-- UPDATE 1: Corregir una dirección.
UPDATE evidencia_casas
SET direccion = 'Dirección ficticia A corregida'
WHERE nombre_casa = 'Casa Prueba A';

-- UPDATE 2: Desactivar una casa de prueba.
UPDATE evidencia_casas
SET estado = 0
WHERE nombre_casa = 'Casa Prueba B';

-- DELETE 1
DELETE FROM evidencia_casas
WHERE nombre_casa = 'Casa Prueba B';

-- DELETE 2
DELETE FROM evidencia_casas
WHERE nombre_casa = 'Casa Prueba C';

-- DROP 1: Retirar la tabla de ejercicios.
DROP TABLE evidencia_casas;