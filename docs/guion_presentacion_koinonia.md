# Guion técnico para la presentación de Koinonía

Este guion sigue las 15 diapositivas de `Koinonia_Etapa1_corregida.pptx` y contrasta su contenido con el informe y el repositorio actual. Está pensado para una exposición de aproximadamente 12 a 15 minutos.

## Reparto sugerido

- Ignacio: diapositivas 1 a 5, problema, alcance y arquitectura.
- Cristofer: diapositivas 6 a 10, organización del código y casos implementados.
- Francisco: diapositivas 11 a 15, modelos, SQL y cierre.

El reparto se puede intercambiar. Lo importante es que quien presente las diapositivas 4 a 6 pueda explicar con seguridad la conexión entre GitHub, Railway, Laravel y MariaDB.

## Idea técnica que debe quedar clara

Hay dos flujos distintos:

1. **Flujo de despliegue:** el equipo sube código a GitHub. Railway observa la rama configurada, obtiene el código, instala las dependencias, construye la aplicación y publica una nueva versión del servicio web.
2. **Flujo de uso:** el navegador entra al dominio público de Railway. Laravel recibe la solicitud, la ruta selecciona un controlador, el controlador valida y consulta MariaDB, y finalmente devuelve una vista HTML o una respuesta JSON.

```text
Equipo -> Git/GitHub -> Railway construye y despliega el backend

Navegador -> dominio público de Railway -> rutas de Laravel
           -> middleware -> controlador -> MariaDB en Railway
           <- Blade/HTML o JSON <- resultado de la consulta
```

GitHub no contiene la base de datos real. Contiene las instrucciones para crearla y trabajar con ella: migraciones, seeders y consultas SQL. Los registros reales están guardados en el servicio MariaDB de Railway.

---

## Diapositiva 1. Koinonía

**Duración sugerida:** 30 segundos.

**Guion oral**

> Buenas. Somos Ignacio Cortés, Cristofer Riveros y Francisco Villagrán. Nuestro proyecto se llama Koinonía y busca centralizar la gestión de las Casas Iglesias. En esta primera etapa diseñamos la solución, construimos el modelo de datos y dejamos funcionando los módulos principales del backend. También desplegamos la aplicación y su base de datos en Railway para poder demostrarla desde internet.

**Transición**

> Antes de explicar las tecnologías, queremos mostrar el problema que dio origen al sistema.

## Diapositiva 2. El problema: todo está repartido

**Duración sugerida:** 45 segundos.

**Guion oral**

> Actualmente la información se reparte entre papel, WhatsApp y documentos sueltos. Eso obliga a los administradores a cruzar datos manualmente, a los líderes a preparar reportes por distintos canales y a los participantes a depender de mensajes informales. El resultado es que cuesta conservar un historial confiable de las Casas, las reuniones y la asistencia. Por eso, en la Etapa 1 priorizamos cuatro elementos: Casas Iglesias, usuarios, reuniones y asistencia. Los eventos y el material de estudio forman parte del diseño general, pero sus módulos todavía están pendientes.

**Idea que deben enfatizar**

El proyecto no intenta reemplazar WhatsApp como medio de conversación. Busca que la información oficial y estructurada tenga una sola fuente confiable.

## Diapositiva 3. Roles previstos en el diseño

**Duración sugerida:** 50 segundos.

**Guion oral**

> El diseño contempla tres roles. El Administrador mantiene usuarios y Casas, y consulta informes. El Líder registra las reuniones y la asistencia de las Casas a su cargo. El Participante tendrá una vista informativa de su Casa y de las próximas actividades. En la base de datos estos roles ya existen y cada usuario puede tener uno. Sin embargo, debemos distinguir diseño de implementación: en esta etapa todavía no existe un inicio de sesión individual ni una autorización completa por rol. La demostración publicada usa un usuario y una contraseña compartidos mediante HTTP Basic.

**Si preguntan cómo funciona el acceso actual**

El middleware `AccesoDemo` protege las rutas de demostración cuando la aplicación no está en ambiente local. Lee `DEMO_USER` y `DEMO_PASSWORD` desde variables de entorno. Este acceso protege la demo, pero no identifica a cada persona ni aplica permisos de Administrador, Líder o Participante.

## Diapositiva 4. Cómo está armado por dentro

**Duración sugerida:** 1 minuto y 30 segundos.

**Qué señalar**

Sigan visualmente la secuencia Navegador, Front-end, Back-end Laravel, Base de datos y Respuesta.

**Guion oral**

> La aplicación usa una arquitectura web cliente-servidor. El navegador solicita una página o envía un formulario al dominio público del servicio en Railway. El front-end está construido con vistas Blade, HTML, CSS y JavaScript. Blade permite que Laravel genere HTML con información obtenida desde el servidor.
>
> La solicitud entra a Laravel por `public/index.php` y se dirige según las rutas definidas en `routes/web.php`. Antes de llegar al controlador puede pasar por el middleware de acceso. El controlador valida los datos y aplica las reglas del negocio. Después usa el componente de base de datos de Laravel para generar o ejecutar consultas.
>
> Laravel se conecta a MariaDB mediante el controlador compatible con MySQL. Los datos de conexión no se escriben en el código. Se leen desde variables de entorno como `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`. MariaDB procesa la consulta y devuelve el resultado. Laravel transforma ese resultado en una vista Blade o en JSON, y la respuesta vuelve al navegador.

**Para qué sirve cada tecnología**

- **PHP 8.4:** ejecuta la lógica en el servidor.
- **Laravel 13:** organiza rutas, middleware, controladores, validaciones, migraciones y acceso a datos.
- **Blade:** genera las páginas HTML desde el servidor.
- **HTML y CSS:** dan estructura y presentación a los formularios y paneles.
- **JavaScript:** realiza interacciones y solicitudes `fetch` en los paneles.
- **Tailwind CSS 4:** está configurado como dependencia de estilos. En la versión actual, varias pantallas propias todavía usan CSS incrustado directamente en sus vistas.
- **Vite 8:** procesa y empaqueta los recursos CSS y JavaScript para producción.
- **Composer:** instala las dependencias PHP y conserva versiones reproducibles mediante `composer.lock`.
- **npm y Node.js:** instalan y ejecutan las herramientas del front-end.
- **MariaDB 11.8:** almacena los datos relacionados del sistema.
- **Git y GitHub:** guardan el historial del código y permiten colaborar.
- **Railway:** ejecuta la aplicación, aloja MariaDB, administra variables de entorno y entrega el dominio público.

**Precisión importante**

Laravel sigue una organización cercana a MVC, pero en esta etapa los controladores consultan principalmente con `DB::table()` y SQL directo. Todavía no se crearon modelos Eloquent específicos para cada entidad del negocio.

## Diapositiva 5. Lo que ya funciona en el back-end

**Duración sugerida:** 1 minuto y 15 segundos.

**Guion oral**

> En el backend ya funcionan los paneles de Casas, Usuarios y Reuniones. También existe un panel para ejecutar y mostrar las tres consultas SELECT exigidas en la etapa. El módulo de ejercicios SQL trabaja sobre una tabla auxiliar llamada `evidencia_casas`, por lo que el DROP de la demostración no elimina ninguna tabla del negocio.
>
> En Railway tenemos dos responsabilidades separadas: un servicio ejecuta Laravel y otro servicio mantiene MariaDB. La aplicación recibe los datos de conexión mediante variables de entorno. La ruta `/salud` ejecuta `SELECT 1`; si responde que la base está conectada, sabemos que el backend logró comunicarse con MariaDB.
>
> Las migraciones reproducen las siete tablas del diseño. Las claves foráneas protegen las relaciones, el campo `estado` permite desactivar Casas y usuarios sin borrar su historial, y el registro de una reunión usa una transacción para guardar la reunión y sus asistentes como una sola operación.

**Cómo se conecta GitHub con Railway**

> GitHub es la fuente del código, no el servidor que ejecuta la aplicación. En la configuración de Railway se conecta el repositorio y una rama, normalmente `main`. Cuando se sube un commit a esa rama, Railway puede construir y desplegar la nueva versión. Esa conexión está configurada en el panel de Railway; no aparece como una carpeta dentro del repositorio. En este proyecto tampoco hay un `Dockerfile`, `Procfile` o `railway.toml`, por lo que no debemos afirmar un comando de construcción concreto sin mostrar la configuración del panel.

**Dónde está la base de datos**

- Los datos reales están en el servicio MariaDB de Railway.
- En GitHub están las migraciones que definen las tablas.
- Los seeders agregan roles y Casas de demostración.
- `database/sql` contiene las consultas académicas y su álgebra relacional.
- `.env` contiene configuración local y está ignorado por Git para no publicar secretos.
- Las variables de producción se configuran en Railway, no se suben a GitHub.

## Diapositiva 6. Estructura de carpetas del proyecto

**Duración sugerida:** 1 minuto y 30 segundos.

**Guion oral**

> Laravel propone una estructura fija para separar responsabilidades. En `app/Http/Controllers` están los controladores que reciben solicitudes, validan datos y coordinan las consultas. Por ejemplo, `ReunionController` verifica la Casa, el líder y los participantes antes de registrar la reunión.
>
> En `app/Http/Middleware` está `AccesoDemo`, que protege las rutas publicadas. En `routes/web.php` definimos qué URL corresponde a cada controlador o vista.
>
> La carpeta `resources/views` contiene las plantillas Blade. `resources/css` y `resources/js` son las entradas que Vite puede procesar para el front-end.
>
> En `database/migrations` se define el esquema de las siete tablas. `database/seeders` contiene datos iniciales y `database/sql` conserva las trece sentencias solicitadas y el álgebra relacional.
>
> `config` contiene la configuración de Laravel. En particular, `config/database.php` toma la conexión desde variables de entorno. `public` es el punto de entrada público, `storage` guarda archivos temporales y registros de la aplicación, y `tests` reúne las pruebas automatizadas.

**Archivos importantes en la raíz**

- `artisan`: herramienta de comandos de Laravel.
- `composer.json` y `composer.lock`: dependencias PHP y versiones exactas.
- `package.json`: dependencias y comandos del front-end.
- `vite.config.js`: conexión entre Vite, Laravel y Tailwind.
- `.env.example`: ejemplo de variables sin secretos.
- `.gitignore`: evita subir `.env`, `vendor`, `node_modules` y recursos generados.

**Dato útil para explicar la relación circular**

La migración de `casa_iglesia` crea inicialmente el campo del líder sin la clave foránea. Después de crear `usuario`, otra migración agrega la referencia. Así se resuelve que una Casa necesita un usuario líder y, al mismo tiempo, un usuario participante puede pertenecer a una Casa.

## Diapositiva 7. Qué hace y qué debe cumplir

**Duración sugerida:** 55 segundos.

**Guion oral**

> Aquí separamos el alcance funcional actual de los criterios de calidad. Hoy podemos crear, editar y desactivar Casas y usuarios, registrar reuniones con asistencia y consultar informes. El login individual, los eventos, el tablero del participante y la publicación de material siguen previstos para una etapa posterior.
>
> Los requisitos no funcionales indican cómo debe comportarse el sistema: proteger credenciales, mantener integridad, ser fácil de usar, estar disponible y permitir mantenimiento. No debemos presentar todos estos requisitos como completamente demostrados. Por ejemplo, el respaldo periódico de MariaDB y las pruebas de rendimiento todavía necesitan evidencia adicional.

## Diapositiva 8. Casos de uso del diseño previsto

**Duración sugerida:** 45 segundos.

**Guion oral**

> Los casos de uso conectan cada necesidad con un actor. El Administrador gestiona Casas y usuarios y consulta informes. El Líder registra reuniones y asistencia. El Participante tendrá acceso informativo. En la Etapa 1 están implementados el mantenimiento básico de Casas y usuarios, el registro de reuniones y las consultas. Autenticación por rol, eventos, material y tablero del participante siguen en el diseño, pero no tienen todavía el flujo completo de rutas, controladores, vistas y permisos.

## Diapositiva 9. Caso de uso: registrar una reunión

**Duración sugerida:** 1 minuto.

**Guion oral**

> El formulario parte seleccionando una Casa activa. Con esa Casa, el backend obtiene al líder responsable y solo carga participantes activos que pertenezcan a ella. Al enviar el formulario, Laravel vuelve a validar todo en el servidor: la Casa debe existir y estar activa; el líder enviado debe ser el líder asignado y tener el rol correcto; y cada asistente debe ser un participante activo de esa misma Casa.
>
> Después se abre una transacción. Primero se inserta la reunión y luego se insertan las filas de asistencia. Si cualquiera de las operaciones falla, la transacción revierte todo. Así evitamos una reunión incompleta o una asistencia sin reunión. Además, la clave primaria compuesta de ASISTENCIA impide marcar dos veces al mismo usuario en una reunión.

**Precisión de seguridad**

El campo `id_lider` se valida contra la Casa, pero todavía proviene del formulario. Cuando exista autenticación individual, la identidad del líder deberá salir de la sesión autenticada y no de un dato enviado por el navegador.

## Diapositiva 10. Caso de uso: consultar informes

**Duración sugerida:** 1 minuto.

**Guion oral**

> El informe exige mes y año para acotar el período. También permite filtrar por líder, Casa, participante y cantidad de asistentes. La consulta une REUNION con CASA_IGLESIA y con el usuario que actualmente figura como líder. Para contar asistentes usa una subconsulta con `COUNT`, y para filtrar por participante usa `EXISTS` sobre ASISTENCIA.
>
> El resultado contiene la reunión, fecha, tema, Casa, líder y cantidad de asistentes. Debemos aclarar dos límites actuales: el líder mostrado es el responsable actual de la Casa, no necesariamente el líder histórico de esa reunión; y la implementación devuelve una lista en JSON, sin una pantalla de detalle individual.

## Diapositiva 11. Mockups

**Duración sugerida:** 40 segundos.

**Guion oral**

> Estos mockups se usaron para definir la experiencia esperada antes de completar toda la interfaz. A la izquierda vemos un historial de reuniones con filtros y tarjetas de resultados. A la derecha aparece el registro de una reunión desde un teléfono, con selección de fecha, tema y asistentes. Son una guía visual, no una captura de la implementación terminada.

**Diferencias que conviene reconocer si el profesor pregunta**

- El mockup del informe muestra un rango de meses; el backend actual solicita mes y año.
- El mockup muestra “Ver detalle”; esa pantalla todavía no está implementada.
- El mockup móvil representa la experiencia futura del líder con sesión individual.

## Diapositiva 12. Modelo entidad-relación

**Duración sugerida:** 1 minuto.

**Guion oral**

> El diseño tiene siete entidades. ROL clasifica a los usuarios. Una Casa tiene un líder responsable y puede tener varios participantes. Una Casa registra muchas reuniones. ASISTENCIA resuelve la relación de muchos a muchos entre usuarios y reuniones: una reunión incluye varios asistentes y un usuario puede asistir a muchas reuniones.
>
> EVENTO puede asociarse a una Casa o ser global si no tiene Casa. MATERIAL_ESTUDIO pertenece al administrador que lo publica. Estas dos entidades ya existen en el esquema para mantener el diseño preparado, aunque sus módulos de aplicación todavía no están completos.

## Diapositiva 13. Modelo relacional

**Duración sugerida:** 55 segundos.

**Guion oral**

> Al pasar al modelo relacional, cada entidad se convierte en una tabla. Las claves primarias identifican cada registro y las claves foráneas garantizan que las referencias existan. En ASISTENCIA usamos como clave primaria la combinación `id_reunion` e `id_usuario`. Esa decisión representa correctamente la relación de muchos a muchos y evita duplicar una persona dentro de la misma reunión.
>
> En las relaciones históricas preferimos restringir borrados físicos. Para usuarios y Casas usamos baja lógica con `estado = 0`. La excepción es la asistencia: si se elimina una reunión, sus filas de asistencia se eliminan en cascada para no dejar registros huérfanos.

## Diapositiva 14. Las consultas SQL

**Duración sugerida:** 1 minuto.

**Guion oral**

> La etapa exige trece sentencias: tres SELECT, tres INSERT, dos UPDATE, dos DELETE, dos ALTER y un DROP. Dos de los SELECT usan JOIN. El SELECT más completo recorre cuatro tablas: parte de REUNION, obtiene la Casa, enlaza ASISTENCIA y finalmente obtiene el nombre del usuario asistente.
>
> Las operaciones de estructura y modificación se ejecutan sobre `evidencia_casas`, una tabla exclusiva de demostración. Primero se prepara la tabla, luego se ejecutan los ALTER, INSERT, UPDATE y DELETE, y al final se elimina esa tabla auxiliar. De esa manera demostramos las sentencias solicitadas sin poner en riesgo las tablas reales. Los tres SELECT también están expresados en álgebra relacional dentro de `database/sql`.

**Si preguntan por la diferencia entre DELETE y baja lógica**

Los dos DELETE son ejercicios académicos sobre la tabla auxiliar. Para los usuarios y Casas reales, la aplicación cambia `estado` a falso para conservar el historial.

## Diapositiva 15. En resumen

**Duración sugerida:** 40 segundos.

**Guion oral**

> En resumen, Koinonía centraliza Casas, usuarios, reuniones y asistencia. Laravel concentra las validaciones y reglas del negocio; MariaDB conserva los datos relacionados; GitHub mantiene el código y su historial; y Railway ejecuta la aplicación y la base de datos en la nube. Esta etapa ya demuestra el flujo principal y las consultas SQL. Las siguientes etapas completarán la autenticación por rol, los eventos, el material de estudio, el tablero del participante y las pruebas de calidad. Muchas gracias.

---

## Explicación corta de la conexión completa

Esta respuesta sirve si el profesor pregunta “¿cómo se conecta todo?”

> Nosotros desarrollamos el código en el repositorio Git de Koinonía y lo compartimos en GitHub. Railway tiene el servicio web conectado a la rama configurada de ese repositorio. Cuando hay una nueva versión, Railway obtiene el código y construye el servicio. Dentro del servicio, Laravel lee variables de entorno que apuntan al servicio MariaDB del mismo proyecto. Cuando un usuario entra al dominio público, la solicitud llega a una ruta de Laravel, pasa por el middleware, entra a un controlador y este consulta MariaDB. El resultado vuelve como HTML generado con Blade o como JSON. GitHub conserva código y definiciones del esquema; MariaDB conserva los datos reales.

## Qué contiene GitHub y qué contiene Railway

| Elemento | GitHub | Railway |
|---|---:|---:|
| Código PHP/Laravel | Sí | Railway obtiene una copia para desplegar |
| Vistas Blade, CSS y JavaScript | Sí | Se construyen y ejecutan en el servicio web |
| Migraciones | Sí | Laravel puede ejecutarlas contra MariaDB |
| Seeders | Sí | Se ejecutan solo cuando corresponde |
| Consultas SQL académicas | Sí | Algunas se ejecutan desde el backend |
| Registros reales de usuarios y reuniones | No | Sí, dentro de MariaDB |
| Contraseñas y variables de producción | No | Sí, como variables de entorno |
| Historial de commits | Sí | Railway registra qué commit desplegó |
| Dominio público | No | Sí |

## Preguntas probables y respuestas breves

### ¿Por qué eligieron Laravel?

Porque reúne en un mismo framework las rutas, validaciones, middleware, migraciones y acceso a base de datos. También impone una estructura conocida, lo que facilita repartir el trabajo y mantener el proyecto.

### ¿Por qué PHP?

PHP ejecuta la lógica del servidor y es el lenguaje sobre el que funciona Laravel. En este proyecto procesa formularios, valida reglas y genera las respuestas.

### ¿Por qué MariaDB?

Porque el problema tiene datos relacionados y reglas de integridad claras. Las claves foráneas y transacciones permiten proteger relaciones entre Casas, usuarios, reuniones y asistencia. Laravel se conecta usando su configuración MySQL, compatible con MariaDB.

### ¿La base de datos está en GitHub?

No. GitHub guarda migraciones y seeders, que describen cómo crear la estructura y datos iniciales. La base real y sus registros están en MariaDB dentro de Railway.

### ¿Qué ocurre si alguien ve el repositorio?

No debería obtener las credenciales, porque `.env` está excluido mediante `.gitignore`. En Railway los secretos se configuran como variables de entorno. Nunca se deben copiar valores reales a `.env.example` ni a una diapositiva.

### ¿Cómo comprueban la conexión con la base?

La ruta `/salud` ejecuta `SELECT 1`. Si la consulta funciona, responde que la base de datos está conectada. Laravel también produciría un error si las variables o la conexión fueran incorrectas.

### ¿Usaron Eloquent?

Laravel lo incluye, pero la implementación de negocio actual usa principalmente Query Builder mediante `DB::table()` y algunas consultas SQL directas. El único modelo presente es el `User` estándar del proyecto base, no los modelos específicos del dominio.

### ¿Usaron realmente Tailwind?

Tailwind está instalado y conectado a Vite. Sin embargo, las pantallas propias actuales usan principalmente CSS incrustado en las vistas Blade. Conviene presentarlo como parte de la infraestructura del front-end y como base para uniformar estilos, no afirmar que toda la interfaz ya fue migrada a clases Tailwind.

### ¿Qué hace una migración?

Es una versión del esquema de la base de datos escrita en PHP. Permite crear las mismas tablas, columnas, índices y restricciones en distintos ambientes de forma ordenada y repetible.

### ¿Qué garantiza la transacción de reunión?

Garantiza que la reunión y todas sus asistencias se guarden juntas. Si falla una inserción, Laravel revierte la operación completa.

### ¿Por qué no borran usuarios y Casas?

Porque tienen relaciones históricas. Cambiar `estado` a inactivo conserva los registros y evita romper reuniones o asistencias anteriores.

### ¿Los roles ya protegen cada función?

No todavía. Los roles están modelados y almacenados, pero el acceso publicado usa una credencial compartida. Falta autenticación individual y middleware de autorización por rol.

### ¿Qué falta implementar?

Autenticación individual, autorización por rol, eventos, materiales de estudio, tablero del participante, detalle de informes, más pruebas automatizadas y evidencia de respaldos y rendimiento.

## Demostración sugerida

Si tienen tres a cuatro minutos para demostrar la aplicación:

1. Abrir `/salud` y mostrar que Laravel se comunica con MariaDB.
2. Entrar a `/panel` con el acceso compartido.
3. Mostrar una Casa y un usuario activos.
4. Registrar una reunión con al menos un asistente.
5. Consultar el informe por mes y año.
6. Abrir `/panel/consultas` y enseñar el SELECT con cuatro tablas.
7. Mostrar `/etapa1/sql` solo si el profesor solicita evidenciar las sentencias de estructura y modificación.

No ejecuten el ejercicio SQL repetidamente durante la presentación si no es necesario. Aunque trabaja con una tabla auxiliar, la demostración gana claridad si se enfoca en el flujo real de la aplicación.

## Observaciones que evitan afirmaciones incorrectas

- No digan que el login por usuario y los permisos por rol ya están terminados.
- No digan que los módulos de eventos y material ya funcionan; por ahora existen sus tablas.
- No digan que GitHub almacena la base real.
- No digan que el mockup corresponde exactamente a la pantalla actual.
- No digan que los informes guardan una tabla propia; se calculan al consultar.
- No digan que existe historial de líderes por reunión; el informe muestra al líder actual de la Casa.
- No prometan respaldos automáticos si no pueden mostrar su configuración en Railway.
- No afirmen un comando de despliegue concreto sin revisar el panel de Railway, porque el repositorio no contiene configuración explícita de Railway.

## Fuentes técnicas consultadas

- Presentación `Koinonia_Etapa1_corregida.pptx`.
- Informe `Informe App Webs - V2.pdf`.
- Código actual de la rama `main` del repositorio Koinonia.
- Documentación de Railway sobre [servicios conectados a GitHub](https://docs.railway.com/services), [despliegues automáticos](https://docs.railway.com/deployments/github-autodeploys), [variables de entorno](https://docs.railway.com/variables) y [red privada entre servicios](https://docs.railway.com/networking/private-networking).
