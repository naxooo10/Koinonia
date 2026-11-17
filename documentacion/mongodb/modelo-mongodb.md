# Diseño documental de Koinonía en MongoDB

## 1. Objetivo

Proponer un modelo documental para MongoDB a partir del modelo
relacional de Koinonía, conservando las entidades del negocio.

El diseño permite que un usuario pertenezca a varias Casas Iglesias,
que un líder dirija varias Casas y que cada reunión registre presencia,
ausencia, estado pendiente y justificación.

Esta documentación describe el diseño propuesto. No implica que
la aplicación existente ya esté conectada a MongoDB.

## 2. Consultas que orientan el diseño

- Obtener las Casas que dirige un líder.
- Consultar las Casas a las que pertenece un usuario.
- Listar usuarios por Casa, rol o estado.
- Registrar reuniones con presencia, ausencia y justificación.
- Consultar reuniones por período y Casa.
- Obtener el historial de asistencia de una persona.
- Consultar eventos por fecha y Casa.
- Consultar materiales de estudio por categoría.

## 3. Destino de las tablas

Se conservan las siete entidades del negocio en seis colecciones.
La asistencia queda integrada dentro de cada reunión.

| Tabla relacional | Destino en MongoDB |
|---|---|
| rol | Colección roles |
| usuario | Colección usuarios |
| casa_iglesia | Colección casas_iglesia |
| reunion | Colección reuniones |
| asistencia | Arreglo asistencia dentro de cada reunión |
| evento | Colección eventos |
| material_estudio | Colección materiales_estudio |

La tabla técnica migrations pertenece a Laravel y no representa
una entidad del negocio.

## 4. Decisiones sobre las relaciones

### Usuario y rol

usuarios.id_rol referencia a roles._id.

Cada usuario tiene un solo rol: Administrador, Líder o Participante.
El catálogo compartido evita repetir el nombre del rol en cada usuario.

Para mostrar el nombre del rol se debe resolver la referencia.

### Usuario y Casas de pertenencia

usuarios.casas_ids contiene un arreglo de identificadores que
referencian casas_iglesia._id.

Un usuario puede pertenecer a varias Casas. El arreglo no debe
contener identificadores duplicados.

Los datos completos de las Casas permanecen en casas_iglesia.
No se copian nombres, direcciones ni estados dentro del usuario.

Para obtener los integrantes de una Casa se consultan los usuarios
cuyo arreglo casas_ids contiene su identificador. No se mantiene
una segunda lista de miembros dentro de la Casa.

Aunque habitualmente una persona participe en una o dos Casas,
esa cantidad no constituye un máximo obligatorio.

Si las pertenencias requieren fechas de ingreso y salida o un
historial propio, se evaluará una colección de membresías.

### Casa y líder responsable

casas_iglesia.id_lider_responsable referencia a usuarios._id.
Puede ser null cuando la Casa no tenga un responsable asignado.

Varias Casas pueden referenciar al mismo usuario. Esto permite
que un líder dirija más de una Casa.

Las Casas que dirige un líder se obtienen consultando
id_lider_responsable. No se almacena otra lista de Casas dirigidas
dentro del usuario.

La aplicación debe comprobar que el responsable tenga el rol Líder.

Dirigir una Casa y pertenecer a ella son relaciones independientes.
Pertenecer a una Casa mediante casas_ids no concede permisos
para gestionar sus reuniones.

### Reunión y Casa

reuniones.id_casa referencia a casas_iglesia._id.

Las reuniones se almacenan como documentos independientes porque
el historial crece con el tiempo. No se acumulan dentro de la Casa.

Esto permite consultar reuniones por fecha y Casa sin cargar
todo su historial.

### Reunión y asistencia

Cada reunión contiene un arreglo asistencia con un registro
por persona esperada.

Cada registro incluye:

- usuario_id: referencia al usuario.
- estado: presente, ausente o pendiente.
- justificacion: motivo de ausencia; texto opcional o null.
  Solo se conserva cuando el estado es ausente.

La reunión guarda además tema (su título) y descripcion, un texto
opcional o null con el resumen del encuentro. Las reuniones
anteriores a este campo no lo tienen y se leen como null.

Una persona sin evaluar permanece pendiente. No se considera
automáticamente ausente.

Un mismo usuario no puede aparecer dos veces en una reunión.
Solo los registros con estado presente se cuentan como asistentes.

Los registros se integran en la reunión porque pertenecen a ella
y normalmente se consultan juntos. Los datos completos de las
personas permanecen en usuarios.

Actualmente las reuniones suelen tener unas 15 personas, aunque
pueden superar esa cantidad. Este volumen es una referencia
del contexto y no constituye un límite obligatorio.

La lista de personas esperadas se conserva dentro de la reunión.
Cambiar posteriormente casas_ids no agrega ni elimina registros
de reuniones anteriores.

Una persona puede tener registros independientes de asistencia
en reuniones de distintas Casas.

Si las reuniones crecen considerablemente, se necesita paginar
la asistencia o predominan consultas independientes del historial
de participación, se evaluará una colección asistencias separada.

### Evento y creador

eventos.id_creador referencia a usuarios._id.

Los eventos tienen fecha y un ciclo de vida propios. Se almacenan
en una colección independiente para evitar que el documento
del creador acumule un historial creciente.

Para mostrar los datos del creador se debe resolver la referencia.

### Evento y Casa

eventos.id_casa referencia a casas_iglesia._id.

Puede ser null cuando se trate de un evento general de la iglesia.

La referencia evita copiar los datos de la Casa y permite consultar
eventos por fecha y Casa asociada.

### Material de estudio y administrador

materiales_estudio.id_admin referencia a usuarios._id.

Los materiales se almacenan en una colección independiente porque
se acumulan y se consultan por categoría.

url_archivo conserva la ubicación del recurso. El contenido binario
del archivo no se incluye en el documento.

## 5. Identificadores y tipos de datos

- Los identificadores existentes se conservan como números en _id.
- Las referencias utilizan el mismo tipo del identificador de destino.
- No es obligatorio convertir los identificadores a ObjectId.
- Los nombres y descripciones se almacenan como textos.
- El estado de activación de usuarios y Casas es booleano.
- Las referencias opcionales pueden contener null.
- casas_ids es un arreglo de identificadores sin duplicados.
- asistencia es un arreglo de objetos.
- El estado de asistencia utiliza presente, ausente o pendiente.
- justificacion contiene texto o null.
- Las contraseñas se representan mediante hashes.

Las fechas se almacenan como BSON Date y se representan en
los ejemplos mediante Extended JSON con $date.

La Z indica UTC. Las fechas de reunión representadas a medianoche
UTC deben tratarse de forma consistente para evitar que cambien
de día al mostrarse en otra zona horaria.

Los datos y hashes de los ejemplos son ficticios.

## 6. Reglas de integridad y permisos

La aplicación debe comprobar que:

- El correo de cada usuario sea único.
- El nombre de cada rol sea único.
- Las referencias apunten a documentos existentes.
- casas_ids no contenga identificadores repetidos.
- Un usuario no aparezca dos veces en la misma reunión.
- El estado de asistencia sea presente, ausente o pendiente.
- El responsable de una Casa tenga el rol Líder.
- Quien registre o modifique una reunión tenga autorización
  sobre la Casa correspondiente.

MongoDB no aplica automáticamente las claves foráneas de MariaDB.
La aplicación debe validar las referencias, los campos y los tipos.

Un índice único sobre el arreglo asistencia no sustituye
la validación de usuarios repetidos dentro de una reunión.

La desactivación de usuarios y Casas conserva sus documentos,
relaciones e historial y permite reactivarlos.

Los cambios de pertenencia no modifican la asistencia histórica.

Eliminar una reunión elimina también su asistencia embebida.

Antes de eliminar roles, usuarios o Casas se deben comprobar
sus referencias para mantener las restricciones del modelo.

## 7. Índices propuestos

Se proponen los siguientes índices:

- Índice único para usuarios.email.
- Índice único para roles.nombre_rol.
- Índice compuesto de fecha e id_casa en reuniones.
- Índice de fecha_hora en eventos.
- Índice de categoria en materiales_estudio.

La utilidad de los índices de consulta deberá comprobarse
con las consultas reales de la aplicación.

## 8. Costos, duplicación y evolución

Las referencias reducen la duplicación, pero requieren consultas
adicionales o agregaciones para mostrar los datos relacionados.

Por ejemplo, asistencia conserva el identificador y el resultado
individual. Los nombres se obtienen consultando usuarios.

De igual forma, casas_ids conserva las pertenencias, mientras
los nombres y direcciones se consultan en casas_iglesia.

Se mantiene una limitación del modelo anterior: mientras no exista
una referencia histórica al líder en la reunión, el líder mostrado
será el responsable actual de la Casa.

Los registros antiguos que solo contienen identificadores de
presentes no permiten reconstruir con certeza las ausencias
ni sus justificaciones. Esa información no debe inventarse.

Si se decide copiar nombres para facilitar consultas, deberá
definirse si representan el valor actual o un dato histórico,
y cómo se mantendrán esas copias.

La separación futura de asistencias o membresías dependerá
del volumen, las consultas y las necesidades de historial.

## 9. Consulta principal de cada colección

| Colección | Consulta principal |
|---|---|
| roles | Consultar los roles disponibles. |
| usuarios | Listar usuarios por rol, Casas de pertenencia o estado. |
| casas_iglesia | Listar Casas y consultar su líder responsable. |
| reuniones | Consultar reuniones por período y Casa, con asistencia individual. |
| eventos | Consultar eventos por fecha y Casa opcional. |
| materiales_estudio | Consultar materiales por categoría y administrador. |

## 10. Archivos JSON de ejemplo

Los ejemplos se encuentran en documentacion/ejemplos-json/.

- roles.json
- usuarios.json
- casas_iglesia.json
- reuniones.json
- eventos.json
- materiales_estudio.json

Los archivos que contienen arreglos agrupan documentos de ejemplo.
Cada objeto representa un documento independiente de la colección.

Camila Muñoz, usuario 2, dirige las Casas 1 y 4.
Julian Gonzalez, usuario 3, pertenece a ambas Casas.

El ejemplo de reunión registra a Julian como presente y a
Valentina Torres como ausente con justificación.