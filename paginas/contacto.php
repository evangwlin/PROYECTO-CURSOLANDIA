<?php
$titulo_pagina = 'Contacto & Soporte';
$menu_activo = '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/funciones.php';
require_once __DIR__ . '/../includes/header.php';

$mensaje_enviado = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mensaje'])) {
    $mensaje_enviado = true;
}

$usuario_logueado = (!empty($_SESSION['usuario_id']) || !empty($_SESSION['usuario']['id']) || !empty($_SESSION['usuario']));
$nombre_default = $_SESSION['usuario_nombre'] ?? $_SESSION['usuario']['nombre'] ?? '';
$email_default  = $_SESSION['usuario_email'] ?? $_SESSION['usuario']['email'] ?? '';
?>

<div class="dashboard-contenedor <?php echo !$usuario_logueado ? 'sin-sidebar' : ''; ?>">

    <?php if ($usuario_logueado): ?>
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <?php endif; ?>

    <main class="dashboard-contenido pagina-contenedor-centrado">

        <!-- HERO BANNER CONTACTO -->
        <section class="pagina-hero-banner">
            <span class="pagina-hero-eyebrow">
                <i class="bi bi-headset"></i> Atención & Soporte
            </span>
            <h1 class="pagina-hero-titulo">Centro de Contacto</h1>
            <p class="pagina-hero-bajada">
                ¿Tenés alguna consulta sobre un curso, activación del Plan Pro o dudas académicas? Escribinos y el equipo de Cursolandia te responderá a la brevedad.
            </p>
        </section>

        <?php if ($mensaje_enviado): ?>
            <div class="alerta-flash exito">
                <i class="bi bi-check-circle-fill"></i>
                <div>
                    <strong>¡Mensaje enviado con éxito!</strong>
                    <span>Recibimos tu consulta. Te enviaremos una respuesta a tu correo electrónico muy pronto.</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="contacto-grid">

            <section class="tarjeta-panel contacto-form-card">
                <div class="contacto-form-cabecera">
                    <div class="contacto-form-icono">
                        <i class="bi bi-chat-heart-fill"></i>
                    </div>
                    <div class="contacto-form-titulos">
                        <h3>Envianos tu mensaje</h3>
                        <span>Completá el formulario para iniciar la consulta</span>
                    </div>
                </div>

                <form action="contacto.php" method="POST" class="contacto-form-body">
                    <div class="contacto-campo">
                        <label for="nombre_contacto">
                            Nombre Completo <span class="campo-requerido">*</span>
                        </label>
                        <input type="text" id="nombre_contacto" name="nombre" class="contacto-input" value="<?php echo htmlspecialchars($nombre_default); ?>" required placeholder="Ej: Martín Z...">
                    </div>

                    <div class="contacto-campo">
                        <label for="email_contacto">
                            Correo Electrónico <span class="campo-requerido">*</span>
                        </label>
                        <input type="email" id="email_contacto" name="email" class="contacto-input" value="<?php echo htmlspecialchars($email_default); ?>" required placeholder="nombre@ejemplo.com">
                    </div>

                    <div class="contacto-campo">
                        <label for="asunto_contacto">
                            Asunto
                        </label>
                        <input type="text" id="asunto_contacto" name="asunto" class="contacto-input" placeholder="Ej: Consulta sobre activación de Plan Pro o Cursos">
                    </div>

                    <div class="contacto-campo">
                        <label for="mensaje_contacto">
                            Mensaje detallado <span class="campo-requerido">*</span>
                        </label>
                        <textarea id="mensaje_contacto" name="mensaje" class="contacto-input contacto-textarea" rows="5" required placeholder="Escribí acá tu mensaje o consulta..."></textarea>
                    </div>

                    <div>
                        <button type="submit" class="contacto-btn-enviar">
                            <i class="bi bi-send-fill"></i>
                            <span>Enviar Consulta</span>
                        </button>
                    </div>
                </form>
            </section>

            <div class="contacto-info-col">

                <div class="tarjeta-panel contacto-info-card">
                    <div class="contacto-info-icono canal">
                        <i class="bi bi-envelope-at-fill"></i>
                    </div>
                    <div class="contacto-info-texto">
                        <h4>Canal Oficial</h4>
                        <p>Correo institucional para consultas generales y soporte:</p>
                        <a href="mailto:soporte@cursolandia.com" class="contacto-info-link">
                            <i class="bi bi-arrow-right-short"></i> soporte@cursolandia.com
                        </a>
                    </div>
                </div>

                <div class="tarjeta-panel contacto-info-card contacto-info-card borde-coral">
                    <div class="contacto-info-icono horario">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <div class="contacto-info-texto">
                        <h4>Horario Académico</h4>
                        <p>Lunes a Viernes de <strong>09:00 a 18:00 hs</strong>.</p>
                        <span class="contacto-badge-tiempo">
                            Respuesta estimada: &lt; 24hs hábiles
                        </span>
                    </div>
                </div>

                <div class="tarjeta-panel contacto-info-card">
                    <div class="contacto-info-icono catedra">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div class="contacto-info-texto">
                        <h4>Cátedra Universitaria</h4>
                        <p>
                            <strong>Tecnicatura Universitaria en Web</strong><br>
                            <span>Ingeniería de Software & Programación III</span>
                        </p>
                    </div>
                </div>

            </div>

        </div>

    </main>

</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
