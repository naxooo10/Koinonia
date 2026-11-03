# Koinonía — Documentación de servicios HTTP y API REST

Entrega 2 · Versión documental 1.0 · 3 de noviembre de 2026

## 1. Introducción y alcance

Koinonía gestiona Casas Iglesias, usuarios, reuniones con asistencia, eventos y materiales de estudio. El backend utiliza Laravel y MongoDB. El frontend combina vistas Blade con solicitudes HTTP para consultar y modificar los recursos.

Este documento describe la implementación existente: hay endpoints que responden JSON, rutas que entregan HTML y operaciones que redirigen después de guardar. No todas las rutas constituyen una API JSON independiente. La autenticación se realiza mediante sesión web; no se implementa autenticación Bearer ni emisión de tokens de API.

La revisión se basa en `routes/web.php`, `bootstrap/app.php`, los controladores, el modelo User y el middleware VerificarRol del proyecto compartido, incorporando las protecciones de UsuarioController agregadas durante la revisión. Los ejemplos son ilustrativos: sus identificadores deben reemplazarse por los disponibles en la base utilizada. No son capturas de respuestas actuales ni un conjunto de registros que se deba importar.

### Dirección base y convenciones

| Elemento | Valor o comportamiento |
| --- | --- |
| Dirección local | `http://127.0.0.1:8000` |
| Prefijo | Las rutas descritas no utilizan `/api` ni versionado `/v1` |
| Identificadores | Enteros positivos; el almacenamiento utiliza `_id` y las respuestas exponen nombres como `id_usuario` o `id_casa` |
| Consultas JSON | Enviar `Accept: application/json` |
| Cuerpo JSON | Enviar además `Content-Type: application/json` |
| Formularios | `application/x-www-form-urlencoded` o `multipart/form-data` |
| Archivos | `multipart/form-data`; el cliente debe generar el boundary |
| Ausencia de resultados | Las consultas que devuelven listas JSON responden `200` con `[]` |
| Fechas de reuniones | `YYYY-MM-DD`, almacenadas como fecha a medianoche UTC |
| Fechas de eventos | Entrada `YYYY-MM-DDTHH:mm`, interpretada en `America/Santiago` y almacenada como instante UTC |

`Accept: application/json` permite recibir errores JSON, pero no convierte automáticamente una vista HTML o una redirección exitosa en JSON. La tabla de cada recurso especifica la respuesta implementada.

## 2. Autenticación y autorización

### Sesión y protección CSRF

1. Solicitar `GET /login` y conservar la cookie de sesión.
2. Obtener el campo `_token` del formulario de esa página.
3. Enviar `POST /login` con `email`, `password` y `_token`, utilizando esa misma sesión.
4. Conservar las cookies actualizadas de la respuesta: el inicio de sesión regenera el identificador de sesión.
5. Enviar el token CSRF de la sesión vigente en las operaciones `POST`, `PATCH` y `DELETE`. Se puede utilizar `_token` en el cuerpo o la cabecera `X-CSRF-TOKEN`.

Después del login puede obtenerse el token vigente desde un formulario protegido, por ejemplo el formulario de cierre de sesión del panel. Después del logout se requiere una nueva sesión para continuar. No se publica una ruta propia `/csrf-token` ni `/sanctum/csrf-cookie`.

Los formularios Blade incorporan `@csrf`. Para formularios HTML que representan `PATCH` o `DELETE`, se envía `POST` junto con `_method=PATCH` o `_method=DELETE`. Para reemplazar archivos se utiliza este mecanismo con `multipart/form-data`.

| Método | Ruta | Acceso | Solicitud | Respuesta exitosa |
| --- | --- | --- | --- | --- |
| GET | `/` | Público | Sin cuerpo | `302` hacia `/login` |
| GET | `/login` | Invitado | Sin cuerpo | `200` HTML; un usuario autenticado es redirigido por `guest` |
| POST | `/login` | Invitado | `email`, `password`, CSRF | `302` hacia la URL protegida pendiente o `/panel` |
| POST | `/logout` | Autenticado | CSRF | `302` hacia `/login`; invalida la sesión |
| GET | `/salud` | Público | Sin cuerpo | `200` JSON si la consulta a MongoDB no falla |
| GET | `/up` | Público | Sin cuerpo | Comprobación de salud de Laravel; no sustituye la consulta MongoDB de `/salud` |

El correo del login debe tener formato válido y la contraseña debe estar presente. Las credenciales incorrectas o una cuenta inactiva producen un error de validación; con `Accept: application/json`, el estado es `422` y el error aparece en `errors.email`. No se devuelve `401` por contraseña incorrecta en esta implementación.

### Roles

| Función | Administrador | Líder | Participante |
| --- | --- | --- | --- |
| Panel | Sí | Sí | Sí |
| Gestión de Casas, usuarios y consulta de roles | Sí | No | No |
| Informes y cuatro consultas de Etapa 1 | Sí | No | No |
| Ejercicios sobre colección temporal | Sí | No | No |
| Registrar, editar y eliminar reuniones | No por estas rutas | Solo Casas activas a su cargo | No |
| Consultar eventos | Todos | Generales, Casas de pertenencia y Casas activas a su cargo | Generales y Casas de pertenencia |
| Crear eventos | Generales o para Casas autorizadas | Para Casas activas a su cargo | No |
| Editar y eliminar eventos | Todos | Solo los creados por él para Casas activas que siguen a su cargo | No |
| Consultar y descargar materiales | Sí | Sí | No |
| Subir, editar y eliminar materiales | Sí | No | No |

El middleware `auth` exige una sesión. `rol` consulta el rol actual del usuario en MongoDB. Una cuenta inactiva que llega a este middleware se desconecta: recibe `401` JSON o una redirección a `/login` con error de formulario. Un rol inexistente o no permitido produce `403`.

La administración no implica acceso a una ruta declarada exclusivamente para Líder. La pertenencia mediante `casas_ids` y la responsabilidad mediante `id_lider_responsable` son relaciones distintas. Un usuario puede pertenecer a varias Casas y un líder puede ser responsable de varias.

## 3. Endpoints por recurso

En las tablas, **A** significa Administrador, **L** Líder y **P** Participante. Todos los accesos de esta sección requieren sesión y cuenta activa.

### 3.1. Roles

| Método | Ruta | Rol | Respuesta |
| --- | --- | --- | --- |
| GET | `/roles` | A | `200` JSON: lista de `id_rol` y `nombre_rol`, ordenada por ID |

No se exponen rutas HTTP para crear, modificar o eliminar roles.

### 3.2. Casas Iglesias

| Método | Ruta | Rol | Respuesta exitosa |
| --- | --- | --- | --- |
| GET | `/casas` | A | `200` JSON, todas las Casas ordenadas por nombre |
| GET | `/casas/crear` | A | `200` HTML del formulario |
| POST | `/casas` | A | `302` hacia `/panel/casas` |
| GET | `/casas/{id}/editar` | A | `200` HTML del formulario |
| PATCH | `/casas/{id}` | A | `302` hacia `/panel/casas` |
| PATCH | `/casas/{id}/desactivar` | A | `302` hacia `/panel/casas` |
| PATCH | `/casas/{id}/activar` | A | `302` hacia `/panel/casas` |

| Campo | Creación | Actualización |
| --- | --- | --- |
| `nombre_casa` | Obligatorio, texto, máximo 100 caracteres | Obligatorio, mismas reglas |
| `direccion` | Obligatorio, texto, máximo 200 caracteres | Obligatorio, mismas reglas |
| `id_lider_responsable` | No se asigna desde esta operación | Opcional o null; entero positivo de un usuario activo con rol Líder |

Una Casa nueva se crea activa y sin líder. Para asignar líder se utiliza la actualización. Omitir o vaciar `id_lider_responsable` en la actualización deja la Casa sin responsable. No hay endpoint de eliminación permanente de Casas. Activar y desactivar solo modifican `estado`.

La lista devuelve `id_casa`, `nombre_casa`, `direccion`, `estado` e `id_lider_responsable`.

### 3.3. Usuarios

| Método | Ruta | Rol | Respuesta exitosa |
| --- | --- | --- | --- |
| GET | `/usuarios` | A | `200` JSON, usuarios ordenados por nombre |
| GET | `/usuarios/crear` | A | `200` HTML |
| POST | `/usuarios` | A | `302` hacia `/panel/usuarios` |
| GET | `/usuarios/{id}/editar` | A | `200` HTML |
| PATCH | `/usuarios/{id}` | A | `302` hacia `/panel/usuarios` |
| PATCH | `/usuarios/{id}/desactivar` | A | `302` hacia `/panel/usuarios` |
| PATCH | `/usuarios/{id}/activar` | A | `302` hacia `/panel/usuarios` |

| Campo | Creación | Actualización |
| --- | --- | --- |
| `nombre` | Obligatorio, texto, máximo 100 | Obligatorio, mismas reglas |
| `email` | Obligatorio, correo válido, máximo 100, no registrado | Obligatorio, único excluyendo al usuario editado |
| `password` | Obligatorio, texto, mínimo 8 | No se modifica por este endpoint |
| `id_rol` | Obligatorio, entero positivo de un rol existente | Opcional; si aparece debe ser un rol existente |
| `casas_ids` | Arreglo de ID positivos distintos; si se omite se usa `[]` | Opcional; `[]` elimina las pertenencias |
| `actualizar_casas` | No requerido | Con valor verdadero, la ausencia de `casas_ids` se interpreta como `[]` |

Las nuevas pertenencias requieren Casas activas y existentes. Al editar pueden conservarse pertenencias anteriores a Casas inactivas. El usuario nuevo queda activo. La contraseña se guarda mediante hash.

Reglas de protección incorporadas:

- No se permite desactivar la propia cuenta: `403`.
- No se permite quitarse el rol Administrador desde la propia sesión: `422`, campo `id_rol` en JSON.
- Debe permanecer un administrador activo; el control verifica si existe otro antes de desactivar o cambiar el rol del administrador objetivo.
- Antes de desactivar un usuario activo se exige reasignar todas sus Casas como responsable, tanto activas como inactivas: `403`.
- Antes de cambiar a un rol distinto de Líder a un usuario con Casas a cargo se exige reasignarlas: `422`, campo `id_rol`.

La respuesta de listado contiene `id_usuario`, `nombre`, `email`, `estado`, `id_rol`, `nombre_rol`, `casas_ids`, `casas` y `nombre_casa` como texto concatenado. No incluye contraseñas. No existe endpoint de eliminación permanente de usuarios ni de recuperación de contraseña implementado en estas rutas.

### 3.4. Reuniones y asistencia

| Método | Ruta | Rol | Solicitud | Respuesta exitosa |
| --- | --- | --- | --- | --- |
| GET | `/reuniones` | L | `page` opcional | `200` HTML, 15 reuniones por página |
| GET | `/reuniones/crear` | L | `id_casa` opcional, entero positivo | `200` HTML; carga integrantes si se selecciona Casa |
| POST | `/reuniones` | L | Datos de reunión y asistencia | `201` JSON con resumen |
| GET | `/reuniones/{id}/editar` | L | ID de reunión | `200` HTML |
| PATCH | `/reuniones/{id}` | L | Fecha, tema y asistencia original | `200` JSON si se solicita JSON; en HTML `302` hacia `/reuniones` |
| DELETE | `/reuniones/{id}` | L | Sin datos de negocio | `200` JSON si se solicita JSON; en HTML `302` hacia `/reuniones` |

El listado y las operaciones quedan restringidos a Casas activas bajo la responsabilidad actual del líder autenticado. Intentar utilizar una Casa ajena o inactiva produce `403`. Un ID de reunión inexistente produce `404`.

| Campo | Reglas |
| --- | --- |
| `id_casa` | Obligatorio al crear, entero positivo y Casa autorizada; no se modifica al editar |
| `fecha` | Obligatoria, formato `YYYY-MM-DD` |
| `tema` | Obligatorio, texto, máximo 150 |
| `asistencia` | Lista de objetos; al crear, si se omite se normaliza a `[]` |
| `asistencia.*.usuario_id` | Obligatorio, entero positivo, no repetido |
| `asistencia.*.estado` | Si se envía: `presente`, `ausente` o `pendiente`; si se omite: `pendiente` |
| `asistencia.*.justificacion` | Opcional o null, texto de máximo 1000; vacío se normaliza a null |

Cada objeto de asistencia admite únicamente `usuario_id`, `estado` y `justificacion`. Al crear se obtienen todos los integrantes activos de la Casa desde `casas_ids`, independientemente de su rol. Los integrantes no enviados se registran como pendientes; enviar una persona que no pertenece a ese conjunto produce `422`.

Al editar se conserva exactamente el conjunto de integrantes originalmente registrado. Deben enviarse todos esos ID, sin agregar ni eliminar personas. La omisión de la asistencia no permite vaciar una reunión que ya tenía integrantes. El líder se obtiene de la sesión: un `id_lider` enviado por el cliente no determina la autorización.

Eliminar una reunión elimina también su asistencia embebida. No elimina usuarios ni Casas.

### 3.5. Informe de reuniones

`GET /informes/reuniones` · Rol A · Respuesta `200` JSON sin paginación.

| Parámetro de consulta | Requerido | Regla y significado |
| --- | --- | --- |
| `mes` | Sí | Entero de 1 a 12 |
| `anio` | Sí | Entero de 2000 a 2100 |
| `id_lider` | No | Entero positivo; filtra por responsable actual de la Casa |
| `id_casa` | No | Entero positivo |
| `id_participante` | No | Entero positivo; integrante registrado en la asistencia, cualquiera sea su rol o estado de asistencia |
| `cantidad_asistentes` | No | Entero desde 0; cantidad exacta de presentes, sin contar ausentes ni pendientes |

Los filtros se combinan. Un ID positivo sin coincidencias devuelve `[]`; estos filtros no validan la existencia del recurso como una operación de edición. El período comprende desde el inicio UTC del mes hasta antes del inicio UTC del mes siguiente. Los resultados se ordenan por fecha e ID ascendentes.

Cada elemento incluye `id_reunion`, `fecha`, `tema`, `id_casa`, `nombre_casa`, `id_lider`, `nombre_lider`, `cantidad_asistentes`, `cantidad_ausentes`, `cantidad_pendientes` y `asistencia`. Cada registro de asistencia incluye `usuario_id`, `nombre`, `estado` y `justificacion`.

El líder y los nombres se resuelven desde los documentos actuales; no constituyen una fotografía histórica de esos datos. Si la Casa o el usuario ya no están disponibles se devuelven textos de sustitución. El conjunto de ID de asistencia sí se conserva al editar la reunión.

### 3.6. Consultas de Etapa 1

Todas requieren rol A, responden `200` JSON y no requieren parámetros.

| Método y ruta | Resultado y campos |
| --- | --- |
| `GET /etapa1/consultas/1` | Casas activas: `id_casa`, `nombre_casa`, `direccion`; ordenadas por nombre |
| `GET /etapa1/consultas/2` | Casas activas con usuario líder referenciado: `id_casa`, `nombre_casa`, `nombre_lider`; ordenadas por nombre e ID de Casa |
| `GET /etapa1/consultas/3` | Una fila por asistencia presente: `id_reunion`, `fecha`, `tema`, `nombre_casa`, `nombre_asistente`; orden por fecha, ID y nombre |
| `GET /etapa1/consultas/4` | Resumen por Casa con reuniones: `id_casa`, `nombre_casa`, `cantidad_reuniones`, `total_presentes`; ordenado por nombre e ID de Casa |

La consulta 2 utiliza `$lookup` y `$unwind`: una Casa sin usuario responsable relacionado no aparece. Esa consulta no agrega una comprobación de rol o estado del usuario relacionado. La consulta 3 también requiere coincidencia de Casa y usuario. La consulta 4 incluye Casas con reuniones aunque estén inactivas; si falta la Casa utiliza un texto de sustitución. `total_presentes` suma presencias entre reuniones y no representa personas únicas.

Estas operaciones implementan la transformación de las consultas relacionales mediante filtros, `$lookup`, `$unwind`, `$group`, `$sum`, `$filter`, `$project` y ordenación, según corresponda a cada consulta.

### 3.7. Eventos

| Método | Ruta | Rol | Respuesta exitosa |
| --- | --- | --- | --- |
| GET | `/eventos` | A, L, P | `200` HTML, 15 por página, orden por fecha e ID descendentes |
| GET | `/eventos/crear` | A, L | `200` HTML |
| POST | `/eventos` | A, L | `302` hacia `/eventos` |
| GET | `/eventos/{id}/editar` | A, L | `200` HTML con autorización sobre el evento |
| PATCH | `/eventos/{id}` | A, L | `302` hacia `/eventos` |
| DELETE | `/eventos/{id}` | A, L | `302` hacia `/eventos` |

| Campo de creación y actualización | Reglas |
| --- | --- |
| `titulo` | Obligatorio, texto, máximo 150 |
| `descripcion` | Obligatoria, texto, máximo 3000 |
| `fecha_hora` | Obligatoria, formato `YYYY-MM-DDTHH:mm`, fecha y hora válidas para Santiago |
| `id_casa` | Opcional o null para evento general de Administrador; para Líder se exige Casa activa a su cargo |

`id_creador` se determina desde la sesión. Solo Administrador puede crear eventos generales. El Administrador puede conservar la Casa inactiva ya asociada al editar un evento; no puede asignar una nueva Casa inactiva. El Líder solo puede editar o eliminar sus propios eventos si la Casa permanece activa y bajo su responsabilidad. El Participante no puede modificar eventos.

El listado muestra fechas y horas de Santiago; no filtra exclusivamente eventos futuros. No se implementa una respuesta JSON de listado para `/eventos`.

### 3.8. Materiales de estudio

| Método | Ruta | Rol | Solicitud | Respuesta exitosa |
| --- | --- | --- | --- | --- |
| GET | `/materiales` | A, L | `categoria` y `page` opcionales | `200` HTML, 15 por página |
| GET | `/materiales/{id}/descargar` | A, L | ID del material | `200` archivo adjunto |
| GET | `/materiales/crear` | A | Sin cuerpo | `200` HTML |
| POST | `/materiales` | A | Formulario multipart | `302` hacia `/materiales` |
| GET | `/materiales/{id}/editar` | A | ID del material | `200` HTML |
| PATCH | `/materiales/{id}` | A | Metadatos y archivo opcional | `302` hacia `/materiales` |
| DELETE | `/materiales/{id}` | A | Sin datos de negocio | `302` hacia `/materiales` |

| Campo | Reglas |
| --- | --- |
| `titulo` | Obligatorio al crear y editar, texto, máximo 150 |
| `categoria` | Obligatoria al crear y editar, texto, máximo 100 |
| `archivo` | Obligatorio al crear; opcional al editar; máximo 10240 KB |
| Formatos admitidos | `pdf`, `doc`, `docx`, `ppt`, `pptx`, `xls`, `xlsx`, `txt`, sujetos a validación de tipo de archivo |

El filtro de categoría es una coincidencia exacta, opcional, de máximo 100 caracteres. El listado se ordena por fecha de subida e ID descendentes. No devuelve JSON por solicitar `Accept: application/json`.

Los archivos se guardan en el disco `local`, bajo `storage/app/private/materiales_estudio`; MongoDB almacena la ruta relativa y los metadatos. El cliente no elige `url_archivo` ni `id_admin`. La descarga pasa por autorización y valida el patrón de la ruta. Devuelve un nombre derivado del título y `X-Content-Type-Options: nosniff`.

Si el archivo no existe, la descarga devuelve `404`. Al reemplazar, se guarda el nuevo archivo, se actualiza MongoDB y después se elimina el anterior. Si falla la escritura en MongoDB, se intenta eliminar el nuevo archivo. La fecha de subida original no cambia al editar. Al eliminar se borra el documento y se intenta eliminar su archivo.

### 3.9. Ejercicios MongoDB

| Método | Ruta | Rol | Respuesta |
| --- | --- | --- | --- |
| GET | `/etapa1/sql` | A | `200` HTML de ejercicios |
| POST | `/etapa1/sql/ejecutar` | A | `200` JSON con el resultado de ejecución |

No requiere campos de negocio; sí requiere CSRF. Los nombres de ruta y controlador conservan la denominación histórica SQL, pero la implementación utiliza MongoDB.

Cada ejecución crea una colección `evidencia_casas_` con sufijo aleatorio, aplica dos cambios al validador con `collMod`, inserta tres documentos, actualiza dos, elimina dos y elimina la colección temporal en `finally`. No recibe SQL ni código arbitrario del cliente y no opera sobre las Casas reales.

El JSON incluye `motor`, `coleccion_temporal`, `cambios_validacion_ejecutados`, `insert_documentos`, `update_documentos`, `delete_documentos`, `antes_de_eliminar`, `despues_de_eliminar` y `coleccion_temporal_eliminada`. Un fallo de creación, modificación o limpieza no debe interpretarse como ejecución exitosa.

### 3.10. Vistas del panel

| Método | Ruta | Rol | Respuesta |
| --- | --- | --- | --- |
| GET | `/panel` | A, L, P | `200` HTML específico del rol |
| GET | `/panel/casas` | A | `200` HTML |
| GET | `/panel/usuarios` | A | `200` HTML |
| GET | `/panel/reuniones` | A | `200` HTML; consume `/informes/reuniones` |
| GET | `/panel/consultas` | A | `200` HTML; consume las cuatro consultas |

## 4. Ejemplos de solicitudes y respuestas

Todos los ejemplos protegidos presuponen una sesión del rol indicado. En operaciones de escritura se añade el token CSRF vigente. Los campos `Cookie` y `X-CSRF-TOKEN` contienen valores de la sesión real, nunca los textos ilustrativos de este documento.

### 4.1. Inicio de sesión

```http
POST /login HTTP/1.1
Content-Type: application/x-www-form-urlencoded
Accept: application/json
Cookie: <cookie de sesión obtenida en GET /login>

email=usuario%40example.com&password=<contraseña>&_token=<token del formulario>
```

Éxito: `302 Found`, habitualmente `Location: http://127.0.0.1:8000/panel`, o la URL pendiente de la sesión. El cliente debe conservar las cookies de respuesta. Las credenciales no válidas producen `422` JSON con un error en `email`.

### 4.2. Listar roles

```http
GET /roles HTTP/1.1
Accept: application/json
Cookie: <sesión de Administrador>
```

```json
[
  {"id_rol": 1, "nombre_rol": "Administrador"},
  {"id_rol": 2, "nombre_rol": "Líder"},
  {"id_rol": 3, "nombre_rol": "Participante"}
]
```

### 4.3. Crear una Casa

```http
POST /casas HTTP/1.1
Accept: application/json
Content-Type: application/json
Cookie: <sesión de Administrador>
X-CSRF-TOKEN: <token vigente>

{"nombre_casa":"Casa de ejemplo","direccion":"Dirección de ejemplo"}
```

Éxito: `302 Found` hacia `/panel/casas`, incluso con `Accept: application/json`. No devuelve el ID creado en un cuerpo JSON. Si la validación falla, sí responde `422` JSON.

### 4.4. Crear una reunión

```http
POST /reuniones HTTP/1.1
Accept: application/json
Content-Type: application/json
Cookie: <sesión de Líder>
X-CSRF-TOKEN: <token vigente>

{
  "id_casa": 1,
  "fecha": "2026-11-07",
  "tema": "La solidaridad",
  "asistencia": [
    {"usuario_id": 3, "estado": "presente", "justificacion": null},
    {"usuario_id": 4, "estado": "ausente", "justificacion": "Avisó previamente"}
  ]
}
```

Respuesta ilustrativa `201 Created`, suponiendo que esos sean los únicos integrantes activos esperados:

```json
{"id_reunion":10,"asistentes_registrados":1,"ausentes":1,"pendientes":0}
```

Si hay más integrantes activos no enviados, también quedan registrados y el conteo de pendientes aumenta. Para actualizar se usa `PATCH /reuniones/10`, se envían `fecha`, `tema` y todos los integrantes originales. El éxito JSON devuelve el mismo esquema de resumen con estado `200`.

Eliminar con `DELETE /reuniones/10` y `Accept: application/json` devuelve `200`:

```json
{"mensaje":"La reunión y su asistencia se eliminaron correctamente.","id_reunion":10}
```

### 4.5. Consultar el informe

```http
GET /informes/reuniones?mes=11&anio=2026&id_casa=1&cantidad_asistentes=1 HTTP/1.1
Accept: application/json
Cookie: <sesión de Administrador>
```

Respuesta ilustrativa `200 OK`:

```json
[
  {
    "id_reunion": 10,
    "fecha": "2026-11-07",
    "tema": "La solidaridad",
    "id_casa": 1,
    "nombre_casa": "Casa de ejemplo",
    "id_lider": 2,
    "nombre_lider": "Líder de ejemplo",
    "cantidad_asistentes": 1,
    "cantidad_ausentes": 1,
    "cantidad_pendientes": 0,
    "asistencia": [
      {"usuario_id":3,"nombre":"Persona A","estado":"presente","justificacion":null},
      {"usuario_id":4,"nombre":"Persona B","estado":"ausente","justificacion":"Avisó previamente"}
    ]
  }
]
```

### 4.6. Resumen por Casa

`GET /etapa1/consultas/4`, con sesión de Administrador, puede devolver:

```json
[
  {"id_casa":1,"nombre_casa":"Casa de ejemplo","cantidad_reuniones":2,"total_presentes":3}
]
```

### 4.7. Subir o reemplazar un PDF

Para crear: `POST /materiales`, con formulario `multipart/form-data` que contenga `titulo`, `categoria`, `archivo` y `_token`. Ejemplo de valores: título `Guía de solidaridad`, categoría `Estudio bíblico`, archivo `guia.pdf`.

Para reemplazar: `POST /materiales/1` con `_method=PATCH`, los mismos metadatos, `_token` y el nuevo `archivo`. No fijar manualmente la cabecera multipart si se utiliza `FormData` en el navegador.

El éxito redirige a `/materiales` y guarda el mensaje de confirmación en sesión. Para descargar se utiliza `GET /materiales/1/descargar`, con sesión de Administrador o Líder. La respuesta contiene el archivo, no JSON ni la ruta privada del servidor.

### 4.8. Consumo desde Blade con fetch

Este fragmento se utiliza en una vista Blade autenticada de Líder; requiere una Casa autorizada y los ID de integrantes reales:

```blade
<script>
async function registrarReunion(datos) {
    const respuesta = await fetch('/reuniones', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': @json(csrf_token())
        },
        body: JSON.stringify(datos)
    });

    const tipo = respuesta.headers.get('content-type') ?? '';

    if (!tipo.includes('application/json')) {
        throw new Error('La respuesta no es JSON. Revisa la sesión.');
    }

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        const errores = Object.values(resultado.errors ?? {}).flat();
        throw new Error(errores.join(' ') || resultado.message || 'Error al guardar.');
    }

    return resultado;
}
</script>
```

No se debe llamar `respuesta.json()` sobre una descarga, una vista HTML o una redirección que termina en HTML.

## 5. Errores y códigos de estado

| Estado | Significado y casos |
| --- | --- |
| `200` | Consulta JSON, vista, descarga u operación JSON completada según el endpoint |
| `201` | Reunión creada |
| `302` | Redirección normal después de login, logout o guardado; también redirección al login de una petición HTML sin sesión |
| `401` | Sin sesión al solicitar JSON; cuenta inactiva detectada por el middleware de rol en una petición JSON |
| `403` | Rol no permitido, Casa ajena/inactiva o protección de desactivación |
| `404` | Ruta o recurso inexistente, archivo no disponible |
| `405` | Método HTTP no permitido para la ruta |
| `419` | Token CSRF ausente, inválido o sesión vencida en una operación protegida |
| `422` | Validación de campos, credenciales inválidas o reglas de negocio representadas como validación |
| `429` | Límite de solicitudes del login excedido |
| `500` | Fallo no controlado de base de datos, almacenamiento u otro error interno |

Un servidor que rechaza el tamaño del cuerpo antes de Laravel también puede producir `413`. Los límites de PHP pueden causar un error de carga antes de la validación del archivo. No todos esos fallos utilizan el mismo formato JSON de Laravel.

### Formato JSON de validación

```json
{
  "message": "El campo mes es obligatorio.",
  "errors": {
    "mes": ["El campo mes es obligatorio."]
  }
}
```

Es un ejemplo con un error. El texto de `message` y las traducciones pueden variar según la regla, el número de errores y el idioma configurado. El cliente debe utilizar los campos y no comparar mensajes completos como identificadores de error.

### Formato JSON de autorización

```json
{"message":"No tienes permiso para acceder a esta función."}
```

Otros ejemplos: `No puedes desactivar tu propia cuenta.` y `Antes de desactivar este usuario, reasigna las Casas que están bajo su responsabilidad.` con `403`.

La aplicación fuerza excepciones JSON en `/informes/reuniones`, en rutas `api/*` si se incorporaran y cuando la petición espera JSON. Para las demás rutas se debe enviar `Accept: application/json` si se desea ese formato de error. Los formularios HTML con validación fallida normalmente redirigen de vuelta con errores en sesión.

## 6. Límites y cuotas

| Límite | Implementación |
| --- | --- |
| Login | `throttle:5,1`: hasta 5 solicitudes por ventana de 1 minuto para la clave calculada por el middleware; al exceder devuelve `429` |
| API general | No hay un límite global adicional declarado en estas rutas |
| Paginación HTML | 15 registros por página en reuniones del Líder, eventos y materiales |
| Listas JSON | Roles, Casas, usuarios, informes y consultas sin paginación implementada |
| Archivo individual | Validación Laravel: máximo 10240 KB |
| PHP en el entorno revisado | `upload_max_filesize=10M`, `post_max_size=12M`; son valores de ese entorno y pueden variar en otro servidor |
| Directorio temporal de carga | Debe existir y ser escribible por PHP antes de que Laravel reciba el archivo |
| Cuota total de almacenamiento | No se implementa una cuota por usuario ni un límite acumulado de materiales |

Los límites de texto y los rangos numéricos se detallan por recurso. No se implementan claves de idempotencia: repetir una creación puede generar otro recurso. La desactivación es un cambio de estado y no elimina el documento.

## 7. Consideraciones de seguridad y límites de la implementación

- La autenticación exige cuenta activa y contraseña válida. Las contraseñas se almacenan mediante hash y no aparecen en los listados públicos del controlador.
- El login regenera la sesión y el logout la invalida. El middleware expulsa cuentas inactivas y comprueba el rol actual en cada solicitud protegida.
- Las escrituras web exigen CSRF. Las rutas sensibles incluyen control de rol y, en reuniones y eventos del Líder, control de responsabilidad sobre la Casa.
- El servidor obtiene la identidad del líder, creador o administrador desde la sesión; no debe confiarse en esos campos enviados por el cliente.
- Las validaciones restringen campos, tipos, tamaños y referencias. Los listados e informes seleccionan datos de respuesta sin exponer hashes.
- La descarga de materiales utiliza una ruta relativa validada y autorización. Los nombres físicos son generados al guardar. La validación MIME y de tamaño no sustituye un análisis antivirus; no se implementa ese análisis.
- Los índices únicos configurados para correo de usuarios y nombre de rol refuerzan la integridad cuando se ejecuta el comando de índices. Una colisión concurrente de correo puede generar una excepción de base de datos; no se implementa su traducción específica a `409`.
- Las comprobaciones de último administrador y de Casas a cargo son validaciones por petición. No incorporan transacciones que garanticen esas reglas frente a modificaciones simultáneas desde varias sesiones.
- No hay configuración propia de acceso entre orígenes para clientes externos ni flujo Bearer. Los ejemplos presuponen consumo web del mismo origen.
- En una publicación real corresponde utilizar HTTPS, cookies seguras y `APP_DEBUG=false`. No se debe incluir `.env`, credenciales reales ni cookies en el repositorio o en el informe. Estas son condiciones de despliegue, no evidencia de que ya exista un servidor publicado.

## 8. Verificación y evidencia para la entrega

Durante la revisión, el responsable del proyecto informó 16 pruebas aprobadas mediante `php artisan test`:

| Grupo | Cantidad | Cobertura |
| --- | --- | --- |
| Ejemplo unitario | 1 | Comprobación básica de PHPUnit; no prueba funcionalidad de negocio |
| Redirección inicial | 1 | `/` redirige a `/login` |
| Acceso de visitantes | 3 | Login disponible, redirecciones HTML y bloqueo JSON sin sesión |
| Middleware de roles | 7 | Rutas temporales con consulta de rol simulada, permisos y expulsión de cuentas inactivas |
| Protección de usuarios | 4 | Cuenta propia, cambio del propio rol y desactivación de Líder con/sin Casas; utiliza MongoDB de pruebas |

Las pruebas de aplicación se configuraron para `Koinonia_testing`, sesiones y caché en memoria. El resultado fue comunicado desde el equipo del responsable; este documento no afirma una nueva ejecución automatizada ni cobertura completa de todos los endpoints.

También se verificaron manualmente funciones de reuniones, eventos, materiales, consultas, roles y protecciones. Para la presentación conviene conservar evidencia de: un login válido, una petición JSON exitosa, un error `422`, un acceso `403`, el informe con sus filtros, el resumen por Casa y la carga/descarga de un PDF.

Para contrastar el inventario en el proyecto se puede ejecutar `php artisan route:list --except-vendor`. Esta documentación refleja las rutas de aplicación revisadas; no inventaría rutas internas que pudieran registrar los paquetes instalados.

## 9. Archivos de referencia del proyecto

| Archivo | Responsabilidad |
| --- | --- |
| `routes/web.php` | Métodos, rutas y middleware de acceso |
| `bootstrap/app.php` | Alias de middleware y representación JSON de excepciones |
| `app/Http/Middleware/VerificarRol.php` | Comprobación de rol y cuenta activa |
| `app/Models/User.php` | Usuario autenticable MongoDB, campos ocultos y conversiones |
| `app/Http/Controllers/AuthController.php` | Login y logout |
| `app/Http/Controllers/RolController.php` | Consulta de roles |
| `app/Http/Controllers/CasaIglesiaController.php` | Gestión de Casas |
| `app/Http/Controllers/UsuarioController.php` | Gestión y protecciones de usuarios |
| `app/Http/Controllers/ReunionController.php` | Reuniones y asistencia |
| `app/Http/Controllers/InformeReunionController.php` | Informe con seis filtros |
| `app/Http/Controllers/ConsultaEtapa1Controller.php` | Cuatro consultas MongoDB |
| `app/Http/Controllers/EventoController.php` | Eventos y alcance por rol/Casa |
| `app/Http/Controllers/MaterialEstudioController.php` | Materiales y descargas |
| `app/Http/Controllers/EjerciciosSqlController.php` | Ejercicios MongoDB en colección temporal |
| `config/filesystems.php` | Disco local privado para materiales |

Los comportamientos documentados deben actualizarse cuando cambien las rutas, los permisos, las validaciones o el formato de respuesta.
