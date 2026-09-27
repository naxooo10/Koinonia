# Álgebra relacional: consultas de la Etapa 1

## SELECT 1. Casas Iglesias activas

π id_casa, nombre_casa, direccion (
    σ estado = 1 (CASA_IGLESIA)
)

Se seleccionan las casas activas y luego se proyectan su ID,
nombre y dirección.

## SELECT 2. Casas activas con su líder actual

π c.id_casa, c.nombre_casa, u.nombre→nombre_lider (
    σ c.estado = 1 (
        CASA_IGLESIA c
        ⋈ c.id_lider_responsable = u.id_usuario
        USUARIO u
    )
)

La reunión entre CASA_IGLESIA y USUARIO identifica al líder
responsable actual. Después se conservan solo las casas activas.

## SELECT 3. Asistentes de cada reunión

π r.id_reunion, r.fecha, r.tema, c.nombre_casa,
  u.nombre→nombre_asistente (
    (
        (
            REUNION r
            ⋈ r.id_casa = c.id_casa
            CASA_IGLESIA c
        )
        ⋈ r.id_reunion = a.id_reunion
        ASISTENCIA a
    )
    ⋈ a.id_usuario = u.id_usuario
    USUARIO u
)

Las relaciones unen la reunión con su casa, sus registros de
asistencia y los usuarios asistentes.