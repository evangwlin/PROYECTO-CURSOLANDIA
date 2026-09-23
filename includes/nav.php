<?php

if (!function_exists('obtener_url_avatar')) {
    require_once __DIR__ . '/../config/funciones.php';
}

if (!isset($base_url) || empty($base_url)) {
    $base_url = function_exists('obtener_base_url') ? obtener_base_url() : '/';
}
?>
<!-- ============================================================
     NAVBAR 
     ============================================================ -->
<header class="navbar">


    <div class="logo">
        <a href="<?php echo $base_url; ?><?php echo (isset($_SESSION['usuario_id']) || isset($_SESSION['usuario'])) ? 'panel/index.php' : 'index.php'; ?>">Cursolandia</a>
    </div>


    <div class="explorar" id="menu-explorar-contenedor">
        <button type="button" id="btn-explorar" onclick="toggleExplorar(event)">
            Explorar
            <svg class="chevron-explorar" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </button>


        <div class="dropdown-explorar" id="dropdown-explorar">
            <div class="dropdown-explorar-seccion">
                <span class="dropdown-subtitulo">Categorías Populares</span>
                <ul class="dropdown-categorias-lista">
                    <li>
                        <a href="<?php echo $base_url; ?>cursos/index.php?categoria=programacion">
                            <span class="drop-cat-icono icono-cat-prog"><i class="bi bi-code-slash"></i></span>
                            <div class="drop-cat-info">
                                <strong>Programación</strong>
                                <small>Fullstack, Backend, PHP, MySQL</small>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>cursos/index.php?categoria=diseno">
                            <span class="drop-cat-icono icono-cat-diseno"><i class="bi bi-palette"></i></span>
                            <div class="drop-cat-info">
                                <strong>Arte & Diseño</strong>
                                <small>UI/UX, CSS Moderno, Gráfico</small>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>cursos/index.php?categoria=ciencia">
                            <span class="drop-cat-icono icono-cat-datos"><i class="bi bi-database"></i></span>
                            <div class="drop-cat-info">
                                <strong>Ciencia & Bases de Datos</strong>
                                <small>Modelado 3FN, SQL Avanzado</small>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>cursos/index.php?categoria=ia">
                            <span class="drop-cat-icono icono-cat-ia"><i class="bi bi-cpu"></i></span>
                            <div class="drop-cat-info">
                                <strong>Inteligencia Artificial</strong>
                                <small>Prompts, Automatización, ML</small>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>cursos/index.php?categoria=marketing">
                            <span class="drop-cat-icono icono-cat-mkt"><i class="bi bi-graph-up-arrow"></i></span>
                            <div class="drop-cat-info">
                                <strong>Negocios & Marketing</strong>
                                <small>Finanzas, Emprendimiento</small>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="dropdown-explorar-pie">
                <a href="<?php echo $base_url; ?>cursos/index.php" class="drop-enlace-completo">
                    <span>Ver todos los cursos del catálogo</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>


    <form class="buscador" action="<?php echo $base_url; ?>cursos/index.php" method="GET">
        <input type="text" placeholder="Buscar cursos..." name="q">
        <button type="submit" aria-label="Buscar"></button>
    </form>


    <?php
    if (isset($_SESSION['usuario']) || isset($_SESSION['usuario_id'])) {
        $nombre = $_SESSION['usuario_nombre'] ?? $_SESSION['usuario']['nombre'] ?? 'Usuario';
        $uid_nav = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
        $avatar_nav = $_SESSION['usuario_avatar'] ?? $_SESSION['usuario']['avatar'] ?? null;
        
        $notificaciones_nav = [];
        $total_no_leidas = 0;
        if ($uid_nav > 0) {
            try {
                if (!function_exists('obtener_conexion')) {
                    require_once __DIR__ . '/../config/database.php';
                }
                $conexion_nav = obtener_conexion();
                $uid_seguro = intval($uid_nav);

                $resUserNav = mysqli_query($conexion_nav, "SELECT nombre, avatar FROM Usuario WHERE id = $uid_seguro LIMIT 1");
                $uNav = $resUserNav ? mysqli_fetch_assoc($resUserNav) : null;
                if ($uNav) {
                    if (!empty($uNav['nombre'])) $nombre = $uNav['nombre'];
                    $avatar_nav = $uNav['avatar'] ?? null;
                    $_SESSION['usuario_avatar'] = $avatar_nav;
                    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
                        $_SESSION['usuario']['avatar'] = $avatar_nav;
                        $_SESSION['usuario']['nombre'] = $nombre;
                    }
                }

                $resNotifNav = mysqli_query($conexion_nav, "
                    SELECT * FROM Notificacion 
                    WHERE usuario_id = $uid_seguro 
                    ORDER BY fecha DESC 
                    LIMIT 30
                ");
                $notificaciones_nav = [];
                if ($resNotifNav) {
                    while ($fila = mysqli_fetch_assoc($resNotifNav)) {
                        $notificaciones_nav[] = $fila;
                    }
                }

                $resCountNoLeidas = mysqli_query($conexion_nav, "SELECT COUNT(*) AS total FROM Notificacion WHERE usuario_id = $uid_seguro AND leida = 0");
                $filaCount = $resCountNoLeidas ? mysqli_fetch_assoc($resCountNoLeidas) : null;
                $total_no_leidas = $filaCount ? intval($filaCount['total']) : 0;
            } catch (Throwable $e) {}
        }
    ?>
        <div class="usuario-sesion">
            <div class="nav-notificaciones">
                <button type="button" class="btn-notificaciones" id="btn-notificaciones" aria-label="Notificaciones" onclick="toggleNotificaciones(event)">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php if ($total_no_leidas > 0): ?>
                        <span class="badge-notif" id="badge-notif"><?php echo $total_no_leidas; ?></span>
                    <?php endif; ?>
                </button>

                <div class="dropdown-notificaciones" id="dropdown-notificaciones">
                    <div class="notif-cabecera">
                        <h4>Notificaciones</h4>
                        <?php if ($total_no_leidas > 0): ?>
                            <span class="notif-badge-nuevas" id="notif-badge-nuevas"><?php echo $total_no_leidas; ?> nuevas</span>
                        <?php endif; ?>
                    </div>

                    <ul class="notif-lista" id="notif-lista">
                        <?php if (!empty($notificaciones_nav)): ?>
                            <?php foreach ($notificaciones_nav as $notif): 
                                $urlDestino = !empty($notif['enlace']) ? $base_url . ltrim($notif['enlace'], '/') : '#';
                                $urlMarcarYIr = $base_url . 'controladores/notificacion_controlador.php?accion=marcar_leida&id=' . (int)$notif['id'] . '&redirect=' . urlencode($urlDestino);
                            ?>
                                <li class="notif-item" id="notif-item-<?php echo $notif['id']; ?>">
                                    <a href="<?php echo htmlspecialchars($urlMarcarYIr); ?>">
                                        <div class="notif-icono <?php echo ($notif['leida'] == 0) ? 'icono-rosa' : 'icono-verde'; ?>">
                                            <i class="bi bi-bell-fill"></i>
                                        </div>
                                        <div class="notif-contenido">
                                            <p><?php echo htmlspecialchars($notif['mensaje']); ?></p>
                                            <span class="notif-tiempo"><?php echo date('d/m/Y H:i', strtotime($notif['fecha'])); ?></span>
                                        </div>
                                    </a>
                                    <a href="<?php echo $base_url; ?>controladores/notificacion_controlador.php?accion=eliminar&id=<?php echo (int)$notif['id']; ?>" class="btn-eliminar-notif" title="Eliminar notificación" aria-label="Eliminar">
                                        <i class="bi bi-x"></i>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="notif-item notif-item-vacio" id="notif-item-vacio" style="padding: 16px; text-align: center; color: var(--color-text-muted); font-size: 0.85rem;">
                                No tenés notificaciones recientes.
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>


            <div class="nav-usuario-card">
                <a href="<?php echo $base_url; ?>perfil/index.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: inherit;">
                    <div class="usuario-avatar-circulo">
                        <?php if (!empty($avatar_nav)): ?>
                            <img src="<?php echo htmlspecialchars(obtener_url_avatar($avatar_nav)); ?>" alt="Avatar" class="avatar-img-circulo">
                        <?php else: ?>
                            <?php echo strtoupper(substr($nombre, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <span class="usuario-nombre-nav"><?php echo htmlspecialchars($nombre); ?></span>
                </a>
                <a href="<?php echo $base_url; ?>auth/salir.php" class="btn-salir" title="Cerrar sesión">Salir</a>
            </div>
        </div>

        <script>
        function toggleNotificaciones(e) {
            if (e) {
                e.stopPropagation();
            }
            var dropdown = document.getElementById('dropdown-notificaciones');
            if (dropdown) {
                dropdown.classList.toggle('activo');
            }
        }

        document.addEventListener('click', function(e) {
            var contenedor = document.querySelector('.nav-notificaciones');
            var dropdown = document.getElementById('dropdown-notificaciones');
            if (contenedor && dropdown && !contenedor.contains(e.target)) {
                dropdown.classList.remove('activo');
            }
        });
        </script>
    <?php
    } else {
    ?>
        <div class="botones-sesion">
            <a href="<?php echo $base_url; ?>auth/registrarse.php" class="btn-registrarse">Registrarse</a>
            <a href="<?php echo $base_url; ?>auth/ingresar.php" class="btn-iniciar-sesion">Iniciar sesión</a>
        </div>
    <?php
    }
    ?>

    <script>
    function toggleExplorar(e) {
        if (e) e.stopPropagation();
        var dropdown = document.getElementById('dropdown-explorar');
        var btn = document.getElementById('btn-explorar');
        if (dropdown) {
            dropdown.classList.toggle('activo');
            if (btn) btn.classList.toggle('activo');
        }
        // cerrar notis
        var notif = document.getElementById('dropdown-notificaciones');
        if (notif) notif.classList.remove('activo');
    }

    document.addEventListener('click', function(e) {
        var contExplorar = document.getElementById('menu-explorar-contenedor');
        var dropExplorar = document.getElementById('dropdown-explorar');
        var btnExplorar = document.getElementById('btn-explorar');
        if (contExplorar && dropExplorar && !contExplorar.contains(e.target)) {
            dropExplorar.classList.remove('activo');
            if (btnExplorar) btnExplorar.classList.remove('activo');
        }
    });
    </script>

</header>