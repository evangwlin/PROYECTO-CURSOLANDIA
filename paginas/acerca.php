<?php
$titulo_pagina = 'Sobre Nosotros';
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

    <main class="dashboard-contenido pagina-contenedor-centrado acerca-pagina">

        <section class="acerca-hero">
            <span class="acerca-eyebrow">
                <i class="bi bi-mortarboard-fill"></i> Proyecto Académico · Cursolandia
            </span>
            <h1 class="acerca-hero-titulo">Aprender y enseñar en una comunidad abierta</h1>
            <p class="acerca-hero-bajada">
                Una plataforma web moderna, colaborativa e inclusiva diseñada para que cualquier persona pueda compartir sus conocimientos, gestionar sus propios cursos o capacitarse con las mejores propuestas formativas.
            </p>
        </section>

        <section class="acerca-seccion-academica">
            <div class="academica-tarjeta">
                <div class="academica-icono-grande">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div class="academica-contenido">
                    <div class="academica-tag">Tecnicatura Universitaria en Web · UNSL</div>
                    <h2>Desarrollo e Ingeniería de Software</h2>
                    <p>
                        <strong>Cursolandia</strong> nació como proyecto integrador para las cátedras de <em>Programación III</em> e <em>Ingeniería de Software</em> de la <strong>Universidad Nacional de San Luis</strong>.
                    </p>
                    <p>
                        El sistema fue diseñado desde sus cimientos respetando una arquitectura en capas, consultas seguras mediante <code>mysqli</code> con sanitización estricta, diseño responsivo sin librerías pesadas y un modelo de datos en <strong>Tercera Forma Normal (3FN)</strong> para garantizar la integridad referencial.
                    </p>
                </div>
            </div>
        </section>

        <section class="acerca-bloque-pilares">
            <div class="seccion-cabecera acerca-pilares-cabecera">
                <h2 class="acerca-pilares-titulo">¿Qué hace especial a Cursolandia?</h2>
                <p class="acerca-pilares-bajada">Una experiencia integral tanto para quienes dan sus primeros pasos como para docentes experimentados.</p>
            </div>

            <div class="acerca-grid">
                <div class="acerca-card">
                    <div class="acerca-icono cursos">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <h3>Cursos Abiertos y PRO</h3>
                    <p>
                        Cualquier miembro puede publicar cursos con cupos limitados, fechas de vigencia, precios o acceso gratuito, gestionando contenidos y materiales adjuntos con topes según su plan.
                    </p>
                </div>

                <div class="acerca-card">
                    <div class="acerca-icono foros">
                        <i class="bi bi-chat-heart-fill"></i>
                    </div>
                    <h3>Foro Interactivo con Notificaciones</h3>
                    <p>
                        Espacio de debate pedagógico con regla anti doble respuesta consecutiva, respuestas marcadas como aceptadas y alertas automáticas en tiempo real en la campanita.
                    </p>
                </div>

                <div class="acerca-card">
                    <div class="acerca-icono pro">
                        <i class="bi bi-stars"></i>
                    </div>
                    <h3>Membresías PRO y Auditoría</h3>
                    <p>
                        Proceso transparente de solicitud y pago con comprobante bancario adjunto, con validación administrativa para activar beneficios extendidos y mayor capacidad.
                    </p>
                </div>
            </div>
        </section>

        <section class="acerca-cta">
            <div class="acerca-cta-contenido">
                <h2>¿Listo para empezar a aprender o enseñar?</h2>
                <p>Explorá nuestro catálogo de propuestas formativas o unite a la comunidad de Cursolandia hoy mismo.</p>
                <div class="acerca-cta-botones">
                    <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-primario">
                        <i class="bi bi-search"></i> Explorar Cursos
                    </a>
                    <?php if (!$usuario_logueado): ?>
                        <a href="<?php echo $base_url; ?>auth/registrarse.php" class="btn-secundario">
                            <i class="bi bi-person-plus-fill"></i> Crear Cuenta Gratis
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $base_url; ?>panel/index.php" class="btn-secundario">
                            <i class="bi bi-speedometer2"></i> Ir a mi Panel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    </main>

</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
