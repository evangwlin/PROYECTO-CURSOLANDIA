<?php
$titulo_pagina = 'Términos y Condiciones';
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

        <header class="dashboard-cabecera">
            <div>
                <h1>Términos y Condiciones de Uso</h1>
                <p>Reglamento oficial y normativas de funcionamiento de la plataforma Cursolandia.</p>
            </div>
        </header>

        <section class="tarjeta-panel normativa-seccion">
            <div class="normativa-cabecera">
                <i class="bi bi-shield-check"></i>
                <h3>1. Planes de Usuario: Gratuito y Pro</h3>
            </div>
            <ul class="normativa-lista">
                <li><strong>Usuarios Regulares (Gratuito):</strong> Pueden tener simultáneamente hasta un máximo de <strong>3 (tres) cursos activos</strong> como creadores y estar inscriptos en hasta <strong>3 (tres) cursos activos</strong> simultáneamente como alumnos.</li>
                <li><strong>Usuarios Pro:</strong> No poseen restricciones en la cantidad de cursos activos creados ni en el número de cursos a los que pueden inscribirse.</li>
                <li><strong>Materiales adjuntos:</strong> Los creadores de plan gratuito pueden subir un límite de hasta 5 (cinco) archivos adjuntos por curso, con un peso máximo individual de 5 MB por archivo y hasta 10 MB en total. Los usuarios Pro disponen de límites ampliados.</li>
                <li><strong>Activación del Plan Pro:</strong> El usuario debe notificar el pago mediante comprobante adjunto desde su configuración. Un administrador evalúa el comprobante y activa la membresía con una fecha de vencimiento determinada.</li>
            </ul>
        </section>

        <section class="tarjeta-panel normativa-seccion">
            <div class="normativa-cabecera">
                <i class="bi bi-mortarboard"></i>
                <h3>2. Creación e Inscripción a Cursos</h3>
            </div>
            <ul class="normativa-lista">
                <li>Cada curso debe contar con título, descripción, categoría, etiquetas de búsqueda, cupo y costo (opcionales), y fecha de inicio y fin dentro de la cual permanecerá activo.</li>
                <li>Un usuario puede anotarse a un curso siempre y cuando no sea el docente creador del mismo y el curso se encuentre en periodo activo.</li>
                <li><strong>Cursos Arancelados:</strong> Al inscribirse a un curso de pago, el estado queda como <em>"pendiente"</em> hasta que el docente creador confirme la acreditación del pago. Los arreglos comerciales se gestionan por fuera de la plataforma.</li>
            </ul>
        </section>

        <section class="tarjeta-panel normativa-seccion">
            <div class="normativa-cabecera">
                <i class="bi bi-chat-square-quote"></i>
                <h3>3. Foros de Debate e Interacción Académica</h3>
            </div>
            <ul class="normativa-lista">
                <li><strong>Regla de respuestas consecutivas:</strong> Ningún usuario puede responder dos (2) veces seguidas a la misma pregunta. Debe editar su última respuesta o esperar a que otro miembro responda.</li>
                <li><strong>Moderación docente:</strong> El creador del curso puede destacar preguntas, responderlas y eliminar contenidos inapropiados.</li>
                <li><strong>Respuesta Aceptada:</strong> Tanto el docente como el creador de una duda pueden marcar una respuesta como <em>"Aceptada"</em>, destacándola en la cabecera y cerrando el debate de dicha pregunta.</li>
                <li><strong>Notificaciones automáticas:</strong> El sistema emite notificaciones a los participantes al crear preguntas, al recibir respuestas, al cerrar temas o al eliminar preguntas.</li>
            </ul>
        </section>

        <div class="normativa-pie-fecha">
            Última actualización: Noviembre 2026 · Cursolandia
        </div>

    </main>

</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
