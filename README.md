# Cursolandia - Programación III
**Tecnicatura Universitaria en Web**  
**Cátedra:** Ingeniería de Software & Programación III  

* **Sitio web en producción (servidor real):** [http://cursolandia.xo.je](http://cursolandia.xo.je)

---

## 1. Cómo levantar el proyecto en local

### Requisitos previos
* Servidor local Apache (XAMPP).
* PHP 8.0 o superior con la extensión `mysqli` habilitada.
* MySQL / MariaDB.

### Pasos de instalación
1. Clonar o copiar la carpeta del proyecto dentro del directorio `htdocs` de XAMPP (por ejemplo: `C:\xampp\htdocs\PROYECTO-CURSOLANDIA`).
2. Iniciar los módulos de **Apache** y **MySQL** desde el panel de XAMPP.
3. Entrar a phpMyAdmin desde el navegador: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
4. Crear una base de datos llamada `cursolandia` con cotejamiento `utf8mb4_general_ci`.
5. Importar el archivo `cursolandia.sql` que está en la raíz del proyecto.
6. Abrir en el navegador: [http://localhost/PROYECTO-CURSOLANDIA/](http://localhost/PROYECTO-CURSOLANDIA/)

---

## 2. Usuarios de prueba

La base de datos ya viene cargada con usuarios de ejemplo para probar todos los roles y planes. Todos tienen la contraseña `123456`:

| Rol / Tipo de Usuario | Correo Electrónico | Contraseña |
| :--- | :--- | :--- |
| **Administrador** | `admin@cursolandia.com` | `123456` |
| **Docente (Plan PRO)** | `carlosgomecito@ejemplo.com` | `123456` |
| **Docente (Plan Gratuito)** | `Sunitoo@ejemplo.com` | `123456` |
| **Alumna (Plan Gratuito)** | `camipecouwu@ejemplo.com` | `123456` |

---

## 3. Problemas encontrados y cómo se resolvieron

### 1. Notificaciones sin auto-avisos y bien segmentadas
* **Problema:** Había que mandar notificaciones en varios momentos (al crear preguntas, responder, cerrar temas, eliminar preguntas o aprobar pagos) sin que al usuario le lleguen avisos de sus propias acciones, y separando bien a quién le llega cada cosa (una pregunta nueva va a todo el curso, pero una respuesta solo a los que participaron en ese hilo).
* **Solución:** Armé funciones centralizadas en `config/funciones.php` (`notificar_usuarios_curso` y `notificar_participantes_pregunta`) que reciben un arreglo `$excluir` para descartar el ID del usuario en sesión y evitar duplicados antes de insertar en la tabla `notificacion`.

### 2. Aprobación previa de cursos por el administrador
* **Problema:** Para que no se publique cualquier cosa directo en el catálogo, los cursos creados por docentes tenían que quedar pendientes de revisión hasta que el administrador los apruebe y avisarle al docente cuando se resuelve.
* **Solución:** Al crear un curso en `curso_controlador.php` se guarda con estado pendiente, se habilitó la gestión en `admin/solicitudes.php` para aprobar o rechazar (disparando una notificación al docente) y en el catálogo general se filtran las consultas con `WHERE estado = 'publicado'`.

### 3. Control de límite de archivos según el plan
* **Problema:** Los usuarios gratuitos no pueden pasar de 5 archivos por curso, con un tope de 5 MB por archivo y 10 MB acumulados en total, tanto al crear el curso como al editarlo después.
* **Solución:** Antes de subir archivos nuevos en `curso_controlador.php`, se consulta cuánto pesan y cuántos archivos ya tiene ese curso en la tabla `material`. Después se recorre `$_FILES` sumando el peso uno por uno y, si se pasa de los topes, se frena la subida y se avisa con un mensaje de error en sesión.

### 4. Reglas del foro (no responder dos veces seguidas y cierre de tema)
* **Problema:** Un usuario no puede responder dos veces seguidas en la misma pregunta (tiene que editar su última respuesta o esperar a otro), y tanto el docente como el autor de la pregunta tienen que poder marcar una respuesta como "Aceptada" para cerrar el hilo.
* **Solución:** En `foro_controlador.php` se consulta el último registro de la tabla `respuesta` para esa pregunta (`ORDER BY id DESC LIMIT 1`); si coincide con el usuario logueado, se bloquea el envío nuevo y en la vista solo se le muestra el botón de editar. Al aceptar una respuesta, se actualiza `cerrada = 1` en `pregunta` y se notifica a los participantes.

### 5. Recomendaciones por intereses y prioridad a cursos PRO
* **Problema:** En el panel había que mostrar cursos recomendados cruzando los intereses del perfil con las etiquetas de los cursos, haciendo que los cursos creados por usuarios PRO salgan destacados primero.
* **Solución:** En `panel/index.php` se separan los intereses del usuario y se busca coincidencia con `LIKE` sobre las etiquetas de los cursos publicados, ordenando el resultado con `ORDER BY (u.plan = 'pro') DESC`.

### 6. Inscripción a cursos pagos y control de cupo
* **Problema:** Un usuario no se podía anotar a su propio curso ni superar 3 cursos activos si tenía plan gratuito, y en los cursos pagos el acceso no podía ser automático porque el pago se acuerda por fuera del sistema.
* **Solución:** En `inscripcion_controlador.php` validé que el usuario no sea el creador del curso y que no pase de 3 inscripciones activas. En los cursos pagos la inscripción queda pendiente hasta que el docente confirme el pago y habilite el acceso.

### 7. Error de "headers already sent" en redirecciones
* **Problema:** Al redirigir con `header("Location: ...")` después de procesar formularios, a veces fallaba si ya se había mandado salida antes.
* **Solución:** Se activó el buffer de salida con `ob_start()` al inicio de `config/database.php` para que las redirecciones no fallen.

### 8. Manejo de rutas y URL base entre local y servidor
* **Problema:** En local el proyecto corre dentro de una subcarpeta (`/PROYECTO-CURSOLANDIA/`) pero en el hosting corre directo en la raíz (`/`), lo que hacía que muchos enlaces, imágenes y redirecciones se rompieran o tiraran error 404 según el entorno.
* **Solución:** Creé la función `obtener_base_url()` en `config/database.php` para armar dinámicamente la ruta base correcta en enlaces, imágenes y redirecciones.

### 9. Estructura flexible del temario de los cursos
* **Problema:** Cada curso puede tener distinta cantidad de módulos y clases, por lo que usar columnas fijas limitaba la carga y visualización del temario.
* **Solución:** En `curso_controlador.php` se estructura el temario en JSON y se guarda en la columna `modulos_contenido`, permitiendo interpretarlo y mostrarlo de forma dinámica en la vista del curso y en el aula virtual.

### 10. Centralización y maquetación de estilos en CSS
* **Problema:** Al principio tenía estilos mezclados en varios archivos PHP y en pantallas más cargadas (como el aula virtual con pestañas, reproductor y foro) se pisaban las reglas o se desacomodaban otras vistas al hacer cambios.
* **Solución:** Saqué los estilos en línea y pasé todo a un único archivo `assets/estilo.css` con clases reutilizables y contenedores ordenados para mantener el diseño prolijo en todo el sitio.

### 11. Control de acceso estricto al aula virtual
* **Problema:** Si alguien no había iniciado sesión o entraba directo por URL al aula (`cursos/aula.php?id=X`), podía ver pantallas internas sin cuenta; y si estaba logueado pero no inscripto, tenía el pago pendiente o se había dado de baja, podía intentar ver clases y materiales privados.
* **Solución:** En `aula.php` primero chequeo que haya sesión iniciada (si no, lo mando a `auth/ingresar.php`) y después valido que sea creador, admin o que tenga inscripción 'activa': si está 'pendiente' lo llevo al pago, y si no está inscripto o canceló su vacante lo devuelvo a la ficha del curso con aviso.

### 12. Redirección en login/registro y navegación según sesión activa
* **Problema:** Si ya tenías sesión abierta y entrabas a `auth/ingresar.php` o `auth/registrarse.php`, te volvía a mostrar los formularios de ingreso, y en la barra de navegación no tenía sentido seguir mostrando los botones de entrar si ya estabas adentro.
* **Solución:** En `auth/ingresar.php` y `auth/registrarse.php` redirijo directo a `panel/index.php` si ya existe sesión activa, y en `includes/nav.php` adapto la barra para ocultar login/registro cuando estás logueado y mostrar el acceso al panel, el avatar y el menú del usuario.

### 13. Guardado y rutas de las fotos de perfil
* **Problema:** Al subir una foto de perfil, a veces no se guardaba bien o se veía en el menú superior pero en los foros del aula y en las tarjetas de docentes salía rota porque las páginas están en distintas subcarpetas y las rutas quedaban desfasadas, además de que si no tenía foto cargada quedaba el cuadrito roto.
* **Solución:** En `perfil_controlador.php` guardo la ruta relativa desde la raíz (`archivos/avatares/avatar_...`) con un timestamp único para no pisar archivos, actualizo la sesión al momento para que se vea sin volver a entrar, y en las vistas uso `$base_url` y muestro un avatar por defecto si todavía no cargó foto.
