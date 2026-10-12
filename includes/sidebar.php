<?php

if (!isset($menu_activo)) {
    $menu_activo = 'inicio';
}

$side_uid = 0;
if (isset($_SESSION['usuario_id'])) {
    $side_uid = (int)$_SESSION['usuario_id'];
} elseif (isset($_SESSION['usuario']['id'])) {
    $side_uid = (int)$_SESSION['usuario']['id'];
}

if ($side_uid <= 0) {
    return;
}

$side_rol = '';
if (isset($_SESSION['usuario_rol'])) {
    $side_rol = $_SESSION['usuario_rol'];
} elseif (isset($_SESSION['usuario']['rol'])) {
    $side_rol = $_SESSION['usuario']['rol'];
}

$side_nombre = 'Usuario';
if (isset($_SESSION['usuario_nombre'])) {
    $side_nombre = $_SESSION['usuario_nombre'];
} elseif (isset($_SESSION['usuario']['nombre'])) {
    $side_nombre = $_SESSION['usuario']['nombre'];
}

$side_avatar = null;
if (isset($_SESSION['usuario_avatar'])) {
    $side_avatar = $_SESSION['usuario_avatar'];
} elseif (isset($_SESSION['usuario']['avatar'])) {
    $side_avatar = $_SESSION['usuario']['avatar'];
}

if (!function_exists('usuario_es_pro')) {
    require_once __DIR__ . '/../config/funciones.php';
}
$side_es_pro = usuario_es_pro($side_uid);

if (!function_exists('obtener_conexion')) {
    require_once __DIR__ . '/../config/database.php';
}
$conexion_side = obtener_conexion();
if ($conexion_side) {
    $resAvSide = mysqli_query($conexion_side, "SELECT nombre, avatar, rol FROM usuario WHERE id = $side_uid LIMIT 1");
    $uSide = $resAvSide ? mysqli_fetch_assoc($resAvSide) : null;
    if ($uSide) {
        if (!empty($uSide['nombre'])) $side_nombre = $uSide['nombre'];
        $side_avatar = isset($uSide['avatar']) ? $uSide['avatar'] : null;
        if (!empty($uSide['rol'])) $side_rol = $uSide['rol'];
        $_SESSION['usuario_avatar'] = $side_avatar;
        $_SESSION['usuario_rol'] = $side_rol;
        if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
            $_SESSION['usuario']['avatar'] = $side_avatar;
            $_SESSION['usuario']['nombre'] = $side_nombre;
            $_SESSION['usuario']['rol'] = $side_rol;
        }
    }
}

$side_es_admin = ($side_rol === 'admin');
$side_iniciales = strtoupper(substr($side_nombre, 0, 2));
?>

<aside class="dashboard-sidebar">
    <div class="sidebar-usuario-resumen">
        <a href="<?php echo $base_url; ?>perfil/index.php" class="sidebar-usuario-link">
            <div class="sidebar-avatar">
                <?php if (!empty($side_avatar)): ?>
                    <img src="<?php echo htmlspecialchars(obtener_url_avatar($side_avatar)); ?>" alt="Avatar" class="sidebar-avatar-img">
                <?php else: ?>
                    <?php echo $side_iniciales; ?>
                <?php endif; ?>
            </div>
            <div class="sidebar-usuario-info">
                <strong><?php echo htmlspecialchars($side_nombre); ?></strong>
                <?php if ($side_es_admin): ?>
                    <span class="sidebar-plan-badge plan-admin">
                        Administrador
                    </span>
                <?php else: ?>
                    <span class="sidebar-plan-badge <?php echo $side_es_pro ? 'plan-pro' : 'plan-gratuito'; ?>">
                        <?php echo $side_es_pro ? 'Plan Pro' : 'Plan Gratuito'; ?>
                    </span>
                <?php endif; ?>
            </div>
        </a>
    </div>

    <nav class="sidebar-menu">
        <ul>
            <li class="<?php echo ($menu_activo === 'inicio') ? 'activo' : ''; ?>">
                <a href="<?php echo $base_url; ?>panel/index.php">
                    <i class="bi bi-house-door nav-bi-icon"></i>
                    <span class="sidebar-texto">Inicio</span>
                </a>
            </li>
            <li class="<?php echo ($menu_activo === 'inscripciones') ? 'activo' : ''; ?>">
                <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php">
                    <i class="bi bi-journal-bookmark-fill nav-bi-icon"></i>
                    <span class="sidebar-texto">Mis inscripciones</span>
                </a>
            </li>
            <li class="<?php echo ($menu_activo === 'cursos') ? 'activo' : ''; ?>">
                <a href="<?php echo $base_url; ?>cursos/mis-cursos.php">
                    <i class="bi bi-mortarboard-fill nav-bi-icon"></i>
                    <span class="sidebar-texto">Mis cursos creados</span>
                </a>
            </li>
            <li class="<?php echo ($menu_activo === 'perfil') ? 'activo' : ''; ?>">
                <a href="<?php echo $base_url; ?>perfil/index.php">
                    <i class="bi bi-person nav-bi-icon"></i>
                    <span class="sidebar-texto">Mi perfil</span>
                </a>
            </li>
            <li class="<?php echo ($menu_activo === 'configuracion') ? 'activo' : ''; ?>">
                <a href="<?php echo $base_url; ?>perfil/configuracion.php">
                    <i class="bi bi-gear nav-bi-icon"></i>
                    <span class="sidebar-texto">Configuración</span>
                </a>
            </li>
            <?php if ($side_rol === 'admin'): ?>
            <li class="<?php echo ($menu_activo === 'admin_solicitudes' || $menu_activo === 'admin') ? 'activo' : ''; ?>">
                <a href="<?php echo $base_url; ?>admin/solicitudes.php">
                    <i class="bi bi-shield-lock-fill nav-bi-icon icono-admin-destacado"></i>
                    <span class="sidebar-texto">Aprobaciones (Admin)</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <?php if (!$side_es_pro && !$side_es_admin): ?>
    <div class="sidebar-tarjeta-pro">
        <span class="pro-tag">PLAN PRO</span>
        <h4>Aprendé y enseñá sin límites</h4>
        <p>Cursos ilimitados, certificados verificados y herramientas avanzadas para tu crecimiento.</p>
        <a href="<?php echo $base_url; ?>perfil/configuracion.php#seccion-plan-pro" class="btn-upgrade-pro">Pasate a Pro</a>
    </div>
    <?php endif; ?>
</aside>
