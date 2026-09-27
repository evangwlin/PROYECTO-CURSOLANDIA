<?php
$titulo_pagina = 'Política de Privacidad';
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
                <h1>Política de Privacidad</h1>
                <p>Protección, resguardo y tratamiento de los datos personales en Cursolandia.</p>
            </div>
        </header>

        <section class="tarjeta-panel normativa-seccion">
            <div class="normativa-cabecera">
                <i class="bi bi-lock"></i>
                <h3>1. Información Recopilada</h3>
            </div>
            <p class="normativa-lista">
                Cursolandia recopila únicamente los datos estrictamente necesarios para la gestión académica y el funcionamiento de la plataforma web:
            </p>
            <ul class="normativa-lista">
                <li>Datos de cuenta: Nombre, apellido, nombre de usuario y correo electrónico único.</li>
                <li>Credenciales de acceso: Las contraseñas se almacenan mediante algoritmos criptográficos seguros (Bcrypt con salt individual). Nunca se guardan en texto plano.</li>
                <li>Perfil público: Fotografía de avatar, biografía, antecedentes profesionales e intereses temáticos para recomendaciones.</li>
                <li>Historial académico: Cursos creados, cursos matriculados, progreso de avance y aportes en foros.</li>
            </ul>
        </section>

        <section class="tarjeta-panel normativa-seccion">
            <div class="normativa-cabecera">
                <i class="bi bi-shield-shaded"></i>
                <h3>2. Uso y Protección de los Datos</h3>
            </div>
            <ul class="normativa-lista">
                <li>La información no se comparte ni comercializa con terceras partes ajenas a la cátedra y al servicio.</li>
                <li>Los comprobantes de pago subidos para solicitudes Pro o aranceles de cursos son de acceso exclusivo para los docentes y administradores involucrados.</li>
                <li>Los usuarios pueden editar o actualizar su información de perfil y cambiar su contraseña en cualquier momento desde su configuración.</li>
            </ul>
        </section>

        <div class="normativa-pie-fecha">
            Compromiso ético y de seguridad informática · Cursolandia 2026
        </div>

    </main>

</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
