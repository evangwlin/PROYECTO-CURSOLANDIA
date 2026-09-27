<?php
$titulo_pagina = 'Preguntas Frecuentes';
$menu_activo = '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/funciones.php';
require_once __DIR__ . '/../includes/header.php';

$usuario_logueado = (!empty($_SESSION['usuario_id']) || !empty($_SESSION['usuario']['id']) || !empty($_SESSION['usuario']));
?>

<div class="dashboard-contenedor <?php echo !$usuario_logueado ? 'sin-sidebar' : ''; ?>">

    <?php if ($usuario_logueado): ?>
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <?php endif; ?>

    <main class="dashboard-contenido pagina-contenedor-centrado">

        <section class="pagina-hero-banner">
            <span class="pagina-hero-eyebrow">
                <i class="bi bi-question-diamond-fill"></i> Centro de Ayuda
            </span>
            <h1 class="pagina-hero-titulo">Preguntas Frecuentes</h1>
            <p class="pagina-hero-bajada">
                Encontrá respuestas detalladas sobre las reglas de la plataforma, inscripciones, foros de debate, membresías Pro y límites del sistema.
            </p>
        </section>

        <div class="faq-acordeon-lista">

            <section class="faq-seccion-grupo">
                <div class="faq-grupo-cabecera">
                    <div class="faq-grupo-icono cursos">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <h2 class="faq-grupo-titulo">Cursos e Inscripciones</h2>
                </div>

                <div class="faq-acordeon-lista">

                    <details class="faq-acordeon-item">
                        <summary>
                            <span>¿Cómo funciona la inscripción a cursos gratuitos vs arancelados?</span>
                            <i class="bi bi-chevron-down"></i>
                        </summary>
                        <div class="faq-acordeon-cuerpo">
                            En los <strong>cursos gratuitos</strong> la matriculación es inmediata y tenés acceso directo al aula virtual y sus foros. En los <strong>cursos arancelados</strong>, al anotarte tu inscripción queda en estado <em>Pendiente</em> hasta que el docente creador confirme que recibió la transferencia bancaria. Los acuerdos de pago se gestionan de forma directa entre alumno y docente.
                        </div>
                    </details>

                    <details class="faq-acordeon-item">
                        <summary>
                            <span>¿Quiénes pueden anotarse y cuándo está activo un curso?</span>
                            <i class="bi bi-chevron-down"></i>
                        </summary>
                        <div class="faq-acordeon-cuerpo">
                            Cualquier usuario puede anotarse a un curso siempre que <strong>no sea el docente creador</strong> de dicho curso. Además, las inscripciones están habilitadas en cualquier momento dentro del periodo comprendido entre la <strong>fecha de inicio y la fecha de fin</strong> estipulada para el curso.
                        </div>
                    </details>

                    <details class="faq-acordeon-item">
                        <summary>
                            <span>¿Cuáles son los límites para subir material de estudio como docente?</span>
                            <i class="bi bi-chevron-down"></i>
                        </summary>
                        <div class="faq-acordeon-cuerpo">
                            Los creadores de curso con plan gratuito pueden adjuntar hasta <strong>5 (cinco) archivos</strong> por curso, con un límite individual de hasta <strong>5 MB por archivo</strong> y hasta <strong>10 MB en total</strong>. Los usuarios de <strong>Plan Pro</strong> no tienen esta restricción y disponen de mayor capacidad para compartir guías, ejercicios y recursos.
                        </div>
                    </details>

                </div>
            </section>

            <section class="faq-seccion-grupo">
                <div class="faq-grupo-cabecera">
                    <div class="faq-grupo-icono planes">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <h2 class="faq-grupo-titulo">Planes de Usuario (Gratuito vs Pro)</h2>
                </div>

                <div class="faq-acordeon-lista">

                    <details class="faq-acordeon-item">
                        <summary>
                            <span>¿Cuántos cursos activos puedo tener con el Plan Gratuito?</span>
                            <i class="bi bi-chevron-down"></i>
                        </summary>
                        <div class="faq-acordeon-cuerpo">
                            Un usuario regular de plan gratuito puede tener como máximo <strong>hasta 3 (tres) cursos activos creados</strong> y estar anotado simultáneamente en <strong>hasta 3 (tres) cursos activos</strong> como alumno. Los usuarios con <strong>Plan Pro no tienen ninguna de estas limitaciones</strong>, pudiendo crear e inscribirse de forma ilimitada.
                        </div>
                    </details>

                    <details class="faq-acordeon-item">
                        <summary>
                            <span>¿Cómo solicito y cómo se activa el Plan Pro?</span>
                            <i class="bi bi-chevron-down"></i>
                        </summary>
                        <div class="faq-acordeon-cuerpo">
                            Desde tu panel, ingresá a <strong>Configuración</strong> en la pestaña <em>Membresía y Plan</em>. Allí podés subir el comprobante de transferencia bancaria y un mensaje para el administrador. El administrador audita el pago desde su panel y activa la condición Pro asignando una <strong>fecha de vencimiento</strong>.
                        </div>
                    </details>

                    <details class="faq-acordeon-item">
                        <summary>
                            <span>¿Por qué mis cursos aparecen destacados si soy Pro?</span>
                            <i class="bi bi-chevron-down"></i>
                        </summary>
                        <div class="faq-acordeon-cuerpo">
                            Por regla de la plataforma, los cursos dictados por docentes con Plan Pro vigente se posicionan con prioridad en la portada principal y en los primeros resultados del catálogo, otorgándoles mayor visibilidad ante los estudiantes.
                        </div>
                    </details>

                </div>
            </section>

            <section class="faq-seccion-grupo">
                <div class="faq-grupo-cabecera">
                    <div class="faq-grupo-icono foros">
                        <i class="bi bi-chat-dots-fill"></i>
                    </div>
                    <h2 class="faq-grupo-titulo">Foro de Dudas e Interacciones</h2>
                </div>

                <div class="faq-acordeon-lista">

                    <details class="faq-acordeon-item">
                        <summary>
                            <span>¿Por qué el sistema no me deja responder dos veces seguidas en una pregunta?</span>
                            <i class="bi bi-chevron-down"></i>
                        </summary>
                        <div class="faq-acordeon-cuerpo">
                            Es una <strong>regla de negocio obligatoria</strong> del sistema. Para evitar spam y monólogos, un usuario no puede contestar dos veces consecutivas en el mismo tema. Si necesitás corregir o agregar información, debés <strong>editar tu última respuesta</strong> o esperar a que otro participante o docente responda en el hilo.
                        </div>
                    </details>

                    <details class="faq-acordeon-item">
                        <summary>
                            <span>¿Quién puede marcar una respuesta como "Aceptada" y qué efecto produce?</span>
                            <i class="bi bi-chevron-down"></i>
                        </summary>
                        <div class="faq-acordeon-cuerpo">
                            Tanto el <strong>creador de la duda</strong> como el <strong>docente del curso</strong> tienen la facultad de marcar una respuesta como <em>Aceptada</em>. Al hacerlo, esa respuesta queda fijada y destacada visualmente con una insignia de solución oficial, se cierra el tema para nuevas intervenciones y se notifica automáticamente a los participantes.
                        </div>
                    </details>

                    <details class="faq-acordeon-item">
                        <summary>
                            <span>¿Cuándo emite notificaciones el sistema en los foros?</span>
                            <i class="bi bi-chevron-down"></i>
                        </summary>
                        <div class="faq-acordeon-cuerpo">
                            El sistema notifica de forma automática en 4 eventos clave:
                            <ul>
                                <li><strong>Al crear una pregunta:</strong> se notifica a todos los alumnos y docentes inscriptos al curso.</li>
                                <li><strong>Al responder una duda:</strong> se notifica a los participantes que ya intervinieron en ese hilo.</li>
                                <li><strong>Al cerrar el tema con respuesta destacada:</strong> se notifica especialmente al autor de la respuesta elegida y a los participantes.</li>
                                <li><strong>Al moderar/eliminar una pregunta:</strong> se envía una notificación privada al autor de la misma.</li>
                            </ul>
                        </div>
                    </details>

                </div>
            </section>

        </div>

        <div class="faq-card-ayuda">
            <div>
                <h3>¿Tenés una consulta específica que no figura acá?</h3>
                <p>Escribinos desde el Centro de Contacto y te responderemos en menos de 24 horas hábiles.</p>
            </div>
            <a href="<?php echo $base_url; ?>paginas/contacto.php" class="faq-btn-contacto">
                <i class="bi bi-envelope-fill"></i> Ir a Contacto y Soporte
            </a>
        </div>

    </main>

</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
