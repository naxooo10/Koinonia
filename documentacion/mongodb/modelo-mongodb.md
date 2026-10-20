# Diseño documental de Koinonía en MongoDB

## 1. Objetivo

Proponer un modelo documental para MongoDB a partir del modelo
relacional de Koinonía, conservando los datos y las relaciones
necesarias para gestionar usuarios, Casas Iglesias, reuniones,
asistencia, eventos y materiales de estudio.

## 2. Consultas que orientan el diseño

- Listar Casas Iglesias y consultar su líder responsable.
- Consultar usuarios, su rol y su casa asignada.
- Registrar reuniones y consultar sus asistentes.
- Consultar reuniones por fecha y Casa Iglesia.
- Consultar eventos por fecha y casa asociada.
- Consultar materiales de estudio por categoría.

Las consultas de eventos y materiales se consideran parte del
diseño previsto, sin afirmar que esas pantallas estén implementadas.

## 3. Destino de las tablas

| Tabla relacional | Destino en MongoDB |
|---|---|
| rol | Colección roles |
| usuario | Colección usuarios |
| casa_iglesia | Colección casas_iglesia |
| reunion | Colección reuniones |
| asistencia | Arreglo asistentes dentro de cada reunión |
| evento | Colección eventos |
| material_estudio | Colección materiales_estudio |

Se proponen seis colecciones. Los datos de asistencia se integran
en los documentos de reuniones.

## 4. Decisiones sobre las relaciones

### Usuario y rol

Cada usuario conserva id_rol como referencia a roles.

Esto evita repetir el nombre del rol en cada usuario y permite
modificarlo en un solo lugar. Mostrar el nombre del rol requiere
resolver la referencia mediante otra consulta o una agregación.

### Usuario y casa asignada

Cada usuario conserva id_casa_asignada como referencia a
casas_iglesia. El valor puede ser null.

No se copia la información de la casa en cada usuario, porque su
nombre, dirección y estado pueden cambiar. Tampoco se mantiene
una lista adicional de miembros dentro de la casa, evitando
actualizar la misma asignación en dos lugares.

### Casa y líder responsable

Cada casa conserva id_lider_responsable como referencia a usuarios.
El valor puede ser null.

El usuario existe independientemente de su responsabilidad como
líder. Referenciarlo evita duplicar sus datos personales, aunque
mostrar su nombre requiere resolver la referencia.

### Reunión y casa

Cada reunión conserva id_casa como referencia a casas_iglesia.

Las reuniones se almacenan por separado porque el historial de una
casa crece con el tiempo. Esto permite consultar reuniones por
fecha y casa sin cargar todo su historial en un único documento.

### Reunión y asistencia

Cada reunión contiene un arreglo asistentes con los identificadores
de los usuarios que asistieron. Estos identificadores referencian
documentos de la colección usuarios.

La asistencia se integra porque se registra y consulta junto con
la reunión. No se copian nombres, correos ni contraseñas.

Como supuesto de diseño, se consideran reuniones de hasta 50 asistentes.
Este número no es un requisito confirmado por el cliente y debe
validarse antes de implementar el modelo. No representa un límite
técnico de MongoDB.

El arreglo no debe contener identificadores repetidos.

El historial completo de reuniones no se guarda dentro del usuario.
Si se requieren reuniones que superen el tamaño previsto o consultas
paginadas de asistencia, se revisará esta decisión y se evaluará
una colección asistencias separada.

### Evento y creador

Cada evento conserva id_creador como referencia a usuarios.

Los eventos tienen su propio ciclo de vida y pueden consultarse
por fecha. No se guardan dentro del creador, evitando que su
documento acumule un historial creciente.

### Evento y casa

Cada evento conserva id_casa como referencia a casas_iglesia.
Puede ser null cuando no esté asociado a una casa.

Esto evita copiar los datos de la casa en cada evento.

### Material de estudio y administrador

Cada material conserva id_admin como referencia a usuarios.

Los materiales se almacenan por separado para consultarlos por
categoría sin cargar el documento del administrador. Se conserva
url_archivo como ubicación del archivo, sin incluir su contenido
binario en el documento.

## 5. Identificadores y tipos de datos

- Los identificadores numéricos existentes se conservan como _id.
- Las referencias utilizan el mismo tipo numérico del _id de destino.
- Los estados se representan mediante valores booleanos.
- Las fechas se almacenan como BSON Date.
- Los ejemplos JSON utilizarán Extended JSON para representar fechas.
- Los campos opcionales mantienen null cuando no existe una asignación.
- Las contraseñas conservan únicamente su hash, nunca texto plano.

Conservar los identificadores facilita relacionar los ejemplos con
los datos originales. MongoDB permite identificadores numéricos;
no es obligatorio convertirlos a ObjectId.

## 6. Reglas de integridad

- El correo de cada usuario debe ser único.
- El nombre de cada rol debe ser único.
- Un usuario no puede repetirse en el arreglo de asistentes.
- Las referencias deben apuntar a documentos existentes.
- Un usuario inactivo conserva su historial.
- Una casa inactiva conserva sus reuniones y relaciones.
- Eliminar una reunión elimina también su asistencia embebida.
- Antes de eliminar documentos referenciados se deben comprobar
  sus relaciones para conservar las restricciones del modelo original.

MongoDB no aplica automáticamente las claves foráneas de MariaDB.
La aplicación debe controlar la existencia de las referencias y
las restricciones de eliminación. Los índices únicos permiten
proteger el correo y el nombre del rol.

## 7. Costos y evolución del diseño

El modelo evita duplicar datos personales y datos de las casas.
A cambio, algunas consultas necesitan resolver referencias.

Si mostrar nombres junto a las referencias se volviera una consulta
muy frecuente y costosa, se podría evaluar una referencia extendida,
copiando solo los datos necesarios y definiendo cómo actualizarlos.

Si una relación crece más de lo previsto o aparecen nuevas consultas,
se revisará la distribución de los documentos. Las decisiones se
basan en las consultas y en el crecimiento esperado de los datos.

## 8. Consulta principal de cada colección

| Colección | Consulta principal |
|---|---|
| roles | Consultar los roles disponibles para asignar uno a un usuario. |
| usuarios | Listar usuarios y filtrarlos por rol, casa asignada o estado. |
| casas_iglesia | Listar casas activas y consultar su líder responsable. |
| reuniones | Consultar reuniones por fecha y casa, junto con sus asistentes. |
| eventos | Consultar eventos por fecha y, opcionalmente, por casa. |
| materiales_estudio | Consultar materiales por categoría y mostrar quién los subió. |

Las referencias permiten relacionar documentos sin copiar los datos
completos de usuarios, roles y casas. Para mostrar nombres asociados
a esas referencias es necesario consultar las colecciones correspondientes.

El arreglo asistentes permite obtener los identificadores de los
asistentes junto con la reunión. Para mostrar sus nombres se consulta
la colección usuarios.