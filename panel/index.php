<?php
$titulo_pagina = 'Inicio';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$base_url = obtener_base_url();
$conexion = obtener_conexion();

$usuario_id = intval($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
if ($usuario_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para acceder al panel.';
    header('Location: ' . $base_url . 'auth/ingresar.php');
    exit;
}

// sube datos de usuario a la bd
$usuarioBD = null;
if ($usuario_id > 0) {
    $resUser = mysqli_query($conexion, "SELECT * FROM Usuario WHERE id = $usuario_id LIMIT 1");
    $usuarioBD = $resUser ? mysqli_fetch_assoc($resUser) : null;
}

$nombre_usuario = $usuarioBD['nombre'] ?? $_SESSION['usuario_nombre'] ?? $_SESSION['usuario']['nombre'] ?? 'Usuario';
$es_pro = ($usuario_id > 0) ? usuario_es_pro($usuario_id) : false;
$interesesUsuario = $usuarioBD['intereses'] ?? $_SESSION['usuario']['intereses'] ?? 'PHP, MySQL, Web';

// inscripciones activas y pendientes
$misInscripciones = [];
if ($usuario_id > 0) {
    $resIns = mysqli_query($conexion, "
        SELECT i.*, c.titulo, c.precio, c.etiquetas, c.imagen AS imagen, c.id AS curso_id, c.docente_id,
               u.nombre AS docente_nombre, u.apellido AS docente_apellido
        FROM Inscripcion i
        JOIN Curso c ON i.curso_id = c.id
        LEFT JOIN Usuario u ON c.docente_id = u.id
        WHERE i.usuario_id = $usuario_id AND i.estado IN ('activa', 'pendiente')
        ORDER BY i.fecha_inscripcion DESC
        LIMIT 4
    ");
    if ($resIns) {
        while ($fila = mysqli_fetch_assoc($resIns)) {
            $misInscripciones[] = $fila;
        }
    }
}

// cursos destacados
$cursosDestacados = [];
$resDest = mysqli_query($conexion, "
    SELECT c.*, u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.plan AS docente_plan
    FROM Curso c
    LEFT JOIN Usuario u ON c.docente_id = u.id
    WHERE c.estado = 'publicado'
    ORDER BY (u.plan = 'pro') DESC, c.id DESC
    LIMIT 3
");
if ($resDest) {
    while ($fila = mysqli_fetch_assoc($resDest)) {
        $cursosDestacados[] = $fila;
    }
}

$interesesLista = array_filter(array_map('trim', explode(',', str_replace(['#', ';'], ',', $interesesUsuario))));
$cursosRecomendados = [];

if (!empty($interesesLista)) {
    $whereParts = [];
    foreach ($interesesLista as $tag) {
        $tagEsc = mysqli_real_escape_string($conexion, $tag);
        $whereParts[] = "c.etiquetas LIKE '%$tagEsc%'";
    }
    $sqlRec = "
        SELECT c.*, u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.plan AS docente_plan
        FROM Curso c
        LEFT JOIN Usuario u ON c.docente_id = u.id
        WHERE c.estado = 'publicado' AND (" . implode(' OR ', $whereParts) . ")
        ORDER BY (u.plan = 'pro') DESC, c.id DESC
        LIMIT 3
    ";
    $resRec = mysqli_query($conexion, $sqlRec);
    if ($resRec) {
        while ($fila = mysqli_fetch_assoc($resRec)) {
            $cursosRecomendados[] = $fila;
        }
    }
}

// fallback
if (empty($cursosRecomendados)) {
    $resRecFallback = mysqli_query($conexion, "
        SELECT c.*, u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.plan AS docente_plan
        FROM Curso c
        LEFT JOIN Usuario u ON c.docente_id = u.id
        WHERE c.estado = 'publicado'
        ORDER BY c.id DESC
        LIMIT 3
    ");
    if ($resRecFallback) {
        while ($fila = mysqli_fetch_assoc($resRecFallback)) {
            $cursosRecomendados[] = $fila;
        }
    }
}

// cursos por categoria
$conteosCategorias = [];
$resCats = mysqli_query($conexion, "
    SELECT cat.slug, COUNT(c.id) AS total
    FROM Categoria cat
    LEFT JOIN Curso c ON (c.id_categoria = cat.id OR LOWER(c.categoria) = LOWER(cat.nombre) OR LOWER(c.categoria) = LOWER(cat.slug)) AND c.estado = 'publicado'
    GROUP BY cat.slug
");
if ($resCats) {
    while ($fila = mysqli_fetch_assoc($resCats)) {
        $conteosCategorias[$fila['slug']] = $fila['total'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor">

    <?php
    $menu_activo = 'inicio';
    require_once __DIR__ . '/../includes/sidebar.php';
    ?>

    <main class="dashboard-contenido">

        <div class="panel-banner-bienvenida">
            <div class="panel-banner-texto">
                <span class="panel-banner-eyebrow">¡Hola, <?php echo htmlspecialchars($nombre_usuario); ?>!</span>
                <h1>¿Qué querés aprender hoy?</h1>
                <p>Explorá cursos de la comunidad, encontrá los que más se adaptan a vos y seguí aprendiendo.</p>
                <div class="panel-banner-acciones">
                    <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-primario">Explorar Cursos</a>
                    <a href="<?php echo $base_url; ?>cursos/crear.php" class="btn-secundario">+ Crear Curso</a>
                </div>
            </div>
        </div>

        <div class="panel-explorar-contenedor">

            <div class="panel-layout-explorar">

                <div class="panel-col-principal">

                    <section class="panel-seccion-categorias">
                        <div class="categorias-cabecera">
                            <h2 class="categorias-titulo-top">Top Categorías</h2>
                            <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-pildora-todas">Todas las categorías</a>
                        </div>
                        <div class="carrusel-categorias">
                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=diseno" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-palette"></i></div>
                                <span class="cat-top-nombre">Arte & Diseño</span>
                                <span class="cat-top-cursos"><?php echo ($conteosCategorias['diseno'] ?? 0); ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=programacion" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-code-slash"></i></div>
                                <span class="cat-top-nombre">Programación</span>
                                <span class="cat-top-cursos"><?php echo ($conteosCategorias['programacion'] ?? 0); ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=comunicacion" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-people"></i></div>
                                <span class="cat-top-nombre">Comunicación</span>
                                <span class="cat-top-cursos"><?php echo ($conteosCategorias['comunicacion'] ?? 0); ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=marketing" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-graph-up-arrow"></i></div>
                                <span class="cat-top-nombre">Marketing</span>
                                <span class="cat-top-cursos"><?php echo ($conteosCategorias['marketing'] ?? 0); ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=finanzas" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-cash-stack"></i></div>
                                <span class="cat-top-nombre">Finanzas</span>
                                <span class="cat-top-cursos"><?php echo ($conteosCategorias['finanzas'] ?? 0); ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=ciencia" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-diagram-3"></i></div>
                                <span class="cat-top-nombre">Ciencia & Datos</span>
                                <span class="cat-top-cursos"><?php echo ($conteosCategorias['ciencia'] ?? 0); ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=ia" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-cpu"></i></div>
                                <span class="cat-top-nombre">Inteligencia Artificial</span>
                                <span class="cat-top-cursos"><?php echo ($conteosCategorias['ia'] ?? 0); ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=idiomas" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-translate"></i></div>
                                <span class="cat-top-nombre">Idiomas</span>
                                <span class="cat-top-cursos"><?php echo ($conteosCategorias['idiomas'] ?? 0); ?> Cursos</span>
                            </a>
                        </div>
                    </section>

                    <!-- inscripciones -->
                    <section class="dashboard-seccion" id="mis-inscripciones">
                        <div class="seccion-titulo-fila">
                            <div>
                                <h2>Mis Inscripciones</h2>
                                <p class="seccion-subtitulo">Continuá tu cursada justo donde la dejaste.</p>
                            </div>
                            <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php" class="enlace-ver-todos">Todas mis inscripciones</a>
                        </div>
                        
                        <?php if (!empty($misInscripciones)): ?>
                            <div class="grilla-cursos-progreso">
                                <?php foreach ($misInscripciones as $ins): 
                                    $tags = array_filter(array_map('trim', explode(',', $ins['etiquetas'] ?? '')));
                                    $tagPrincipal = !empty($tags) ? $tags[0] : '#Web';
                                    if (strpos($tagPrincipal, '#') !== 0) $tagPrincipal = '#' . $tagPrincipal;
                                    $docenteCompleto = trim(($ins['docente_nombre'] ?? '') . ' ' . ($ins['docente_apellido'] ?? ''));
                                    if (empty($docenteCompleto)) $docenteCompleto = 'Carlos Gómez';
                                ?>
                                    <article class="tarjeta-progreso">
                                        <div class="progreso-header">
                                            <span class="progreso-tag"><?php echo htmlspecialchars($tagPrincipal); ?></span>
                                            <?php if ($ins['estado'] === 'pendiente'): ?>
                                                <span style="font-size: 0.72rem; font-weight: 800; color: var(--color-text-main); background: var(--color-secondary-soft); padding: 2px 7px; border-radius: 4px;">Pendiente de pago</span>
                                            <?php else: ?>
                                                <span style="font-size: 0.72rem; font-weight: 800; color: var(--color-primary); background: var(--color-primary-light); padding: 2px 7px; border-radius: 4px;">Activa</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php 
                                            $linkPanelIns = ($ins['estado'] === 'activa') ? ($base_url . "cursos/aula.php?id=" . $ins['curso_id']) : ($base_url . "cursos/pagar.php?id=" . $ins['curso_id']);
                                            $textoBtnPanel = ($ins['estado'] === 'activa') ? 'Ir al Aula' : 'Pagar Curso';
                                        ?>
                                        <h3><a href="<?php echo $linkPanelIns; ?>"><?php echo htmlspecialchars($ins['titulo']); ?></a></h3>
                                        <span class="progreso-docente">Prof. <?php if (!empty($ins['docente_id'])): ?><a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$ins['docente_id']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($docenteCompleto); ?></a><?php else: ?><?php echo htmlspecialchars($docenteCompleto); ?><?php endif; ?></span>
                                        <div class="barra-progreso-contenedor">
                                            <div class="barra-progreso-relleno" style="width: 45%;"></div>
                                        </div>
                                        <div class="progreso-pie">
                                            <span class="progreso-leccion"><?php echo ($ins['estado'] === 'activa') ? 'Cursada activa' : 'Pendiente de confirmación'; ?></span>
                                            <a href="<?php echo $linkPanelIns; ?>" class="btn-continuar"><?php echo $textoBtnPanel; ?></a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="background: #ffffff; border: 1px dashed var(--border-color); border-radius: 14px; padding: 32px 24px; text-align: center;">
                                <i class="bi bi-journal-plus" style="font-size: 2.2rem; color: var(--color-primary); display: block; margin-bottom: 8px;"></i>
                                <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 4px;">Todavía no estás inscripto a ningún curso</h4>
                                <p style="font-size: 0.86rem; color: var(--color-text-muted); margin-bottom: 16px;">¡Explorá nuestro catálogo y sumate a tu primera cátedra hoy mismo!</p>
                                <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-primario" style="display: inline-flex; font-size: 0.85rem;">Explorar Cursos</a>
                            </div>
                        <?php endif; ?>
                    </section>

                    <!-- destacados -->
                    <section class="dashboard-seccion" id="destacados">
                        <div class="seccion-titulo-fila">
                            <div>
                                <h2>Cursos Destacados</h2>
                                <p class="seccion-subtitulo">Los más elegidos por la comunidad esta semana.</p>
                            </div>
                            <a href="<?php echo $base_url; ?>cursos/index.php" class="enlace-ver-todos">Todos los cursos</a>
                        </div>
                        <div class="grilla-cursos">
                            <?php foreach ($cursosDestacados as $cd): 
                                $docNombre = trim(($cd['docente_nombre'] ?? '') . ' ' . ($cd['docente_apellido'] ?? ''));
                                if (empty($docNombre)) $docNombre = 'Carlos Gómez';
                                $tags = array_filter(array_map('trim', explode(',', $cd['etiquetas'] ?? '')));
                                $esProDoc = ($cd['docente_plan'] === 'pro');
                                $imgPort = obtener_portada_curso($cd['imagen'] ?? '', $cd['categoria'] ?? '', (int)$cd['id']);
                            ?>
                                <article class="tarjeta-curso">
                                    <div class="curso-portada-card">
                                        <img src="<?php echo htmlspecialchars($imgPort); ?>" alt="<?php echo htmlspecialchars($cd['titulo']); ?>" class="curso-portada-img" loading="lazy" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imgPort); ?>';">
                                    </div>
                                    <div class="curso-info-card">
                                        <div class="curso-autor-meta">
                                            <?php if (!empty($cd['docente_id'])): ?>
                                                <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$cd['docente_id']; ?>" class="curso-autor-nombre" style="text-decoration: none; color: inherit;"><?php echo htmlspecialchars($docNombre); ?></a>
                                            <?php else: ?>
                                                <strong class="curso-autor-nombre"><?php echo htmlspecialchars($docNombre); ?></strong>
                                            <?php endif; ?>
                                            <span class="curso-separador">•</span>
                                            <span class="curso-fecha"><?php echo date('d M', strtotime($cd['fecha_inicio'])); ?> - <?php echo date('d M', strtotime($cd['fecha_fin'])); ?></span>
                                        </div>
                                        <h3 class="curso-titulo"><a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $cd['id']; ?>"><?php echo htmlspecialchars($cd['titulo']); ?></a></h3>
                                        <div class="curso-pildoras">
                                            <?php foreach (array_slice($tags, 0, 2) as $t): ?>
                                                <span class="pildora-tag">#<?php echo htmlspecialchars(ltrim($t, '#')); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (!empty($cd['cupo_max'])): ?>
                                                <span class="pildora-tag"><i class="bi bi-people"></i> Cupo: <?php echo $cd['cupo_max']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="curso-pie-card">
                                        <div class="curso-precio-bloque">
                                            <?php if ((float)$cd['precio'] <= 0): ?>
                                                <span class="curso-precio gratis">Gratis</span>
                                            <?php else: ?>
                                                <span class="curso-precio">$ <?php echo number_format($cd['precio'], 0, ',', '.'); ?></span>
                                            <?php endif; ?>
                                            <span class="curso-precio-sub">Inscripción abierta</span>
                                        </div>
                                        <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $cd['id']; ?>" class="btn-ver-curso">Ver Curso</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- recomendados -->
                    <section class="dashboard-seccion" id="recomendados">
                        <div class="seccion-titulo-fila">
                            <div>
                                <h2>Recomendados para vos</h2>
                                <p class="seccion-subtitulo">
                                    Según tus intereses:
                                    <?php foreach (array_slice($interesesLista, 0, 3) as $it): ?>
                                        <span class="pill-interes-inline">#<?php echo htmlspecialchars(ltrim($it, '#')); ?></span>
                                    <?php endforeach; ?>
                                </p>
                            </div>
                            <a href="<?php echo $base_url; ?>cursos/index.php" class="enlace-ver-todos">Ver catálogo</a>
                        </div>
                        <div class="grilla-cursos">
                            <?php foreach ($cursosRecomendados as $cr): 
                                $docNombre = trim(($cr['docente_nombre'] ?? '') . ' ' . ($cr['docente_apellido'] ?? ''));
                                if (empty($docNombre)) $docNombre = 'Carlos Gómez';
                                $tags = array_filter(array_map('trim', explode(',', $cr['etiquetas'] ?? '')));
                                $esProDoc = ($cr['docente_plan'] === 'pro');
                                $imgPort = obtener_portada_curso($cr['imagen'] ?? '', $cr['categoria'] ?? '', (int)$cr['id']);
                            ?>
                                <article class="tarjeta-curso">
                                    <div class="curso-portada-card">
                                        <img src="<?php echo htmlspecialchars($imgPort); ?>" alt="<?php echo htmlspecialchars($cr['titulo']); ?>" class="curso-portada-img" loading="lazy" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imgPort); ?>';">
                                    </div>
                                    <div class="curso-info-card">
                                        <div class="curso-autor-meta">
                                            <?php if (!empty($cr['docente_id'])): ?>
                                                <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$cr['docente_id']; ?>" class="curso-autor-nombre" style="text-decoration: none; color: inherit;"><?php echo htmlspecialchars($docNombre); ?></a>
                                            <?php else: ?>
                                                <strong class="curso-autor-nombre"><?php echo htmlspecialchars($docNombre); ?></strong>
                                            <?php endif; ?>
                                            <span class="curso-separador">•</span>
                                            <span class="curso-fecha"><?php echo date('d M', strtotime($cr['fecha_inicio'])); ?> - <?php echo date('d M', strtotime($cr['fecha_fin'])); ?></span>
                                        </div>
                                        <h3 class="curso-titulo"><a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $cr['id']; ?>"><?php echo htmlspecialchars($cr['titulo']); ?></a></h3>
                                        <div class="curso-pildoras">
                                            <?php foreach (array_slice($tags, 0, 2) as $t): ?>
                                                <span class="pildora-tag">#<?php echo htmlspecialchars(ltrim($t, '#')); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="curso-pie-card">
                                        <div class="curso-precio-bloque">
                                            <?php if ((float)$cr['precio'] <= 0): ?>
                                                <span class="curso-precio gratis">Gratis</span>
                                                <span class="curso-precio-sub">Acceso inmediato</span>
                                            <?php else: ?>
                                                <span class="curso-precio">$ <?php echo number_format($cr['precio'], 0, ',', '.'); ?></span>
                                                <span class="curso-precio-sub">Inscripción abierta</span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $cr['id']; ?>" class="btn-ver-curso">Ver Curso</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                </div>

                <aside class="panel-col-lateral">

                    <div class="lateral-cabecera-stat" style="margin-bottom: 14px; padding: 0 4px;">
                        <h3 class="lateral-card-titulo" style="display: flex; align-items: center; gap: 8px; font-size: 1rem;">
                            <i class="bi bi-broadcast" style="color: var(--color-primary);"></i> Cursos y Talleres en Vivo
                        </h3>
                        <span class="badge-anuncio-vivo" style="margin: 0; background: var(--color-primary-light); color: var(--color-primary); border-color: rgba(166, 109, 114, 0.25);">
                            Gratis
                        </span>
                    </div>

                    <div class="lateral-card lateral-card-anuncio">
                        <span class="badge-anuncio-vivo"><i class="bi bi-broadcast"></i> En Vivo</span>
                        <h4>Masterclass: Arquitectura Web</h4>
                        <p>Sesión interactiva en vivo con tutores invitados sobre buenas prácticas en PHP y SQL.</p>
                        <div class="anuncio-meta">
                            <i class="bi bi-calendar3"></i>
                            <span>Viernes · 20:00 hs</span>
                        </div>
                        <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-anuncio-accion">Reservar lugar gratis</a>
                    </div>

                    <div class="lateral-card lateral-card-anuncio">
                        <span class="badge-anuncio-vivo badge-naranja"><i class="bi bi-broadcast"></i> En Vivo</span>
                        <h4>Workshop: APIs RESTful con PHP</h4>
                        <p>Diseño de endpoints seguros, autenticación con JWT y conexión a bases de datos.</p>
                        <div class="anuncio-meta">
                            <i class="bi bi-calendar3"></i>
                            <span>Sábado · 17:00 hs</span>
                        </div>
                        <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-anuncio-accion">Reservar lugar gratis</a>
                    </div>

                    <div class="lateral-card lateral-card-anuncio">
                        <span class="badge-anuncio-vivo"><i class="bi bi-broadcast"></i> En Vivo</span>
                        <h4>Bootcamp: Bases de Datos y SQL Avanzado</h4>
                        <p>Clase práctica sobre normalización 3FN, transacciones seguras e indexación eficiente.</p>
                        <div class="anuncio-meta">
                            <i class="bi bi-calendar3"></i>
                            <span>Próximo Martes · 19:00 hs</span>
                        </div>
                        <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-anuncio-accion">Reservar lugar gratis</a>
                    </div>

                    <div class="lateral-card lateral-card-anuncio">
                        <span class="badge-anuncio-vivo badge-naranja"><i class="bi bi-broadcast"></i> En Vivo</span>
                        <h4>Live Coding: Frontend con React & TypeScript</h4>
                        <p>Desarrollo de interfaces reactivas, hooks personalizados y consumo de APIs en tiempo real.</p>
                        <div class="anuncio-meta">
                            <i class="bi bi-calendar3"></i>
                            <span>Miércoles · 19:30 hs</span>
                        </div>
                        <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-anuncio-accion">Reservar lugar gratis</a>
                    </div>

                    <div class="lateral-card lateral-card-anuncio">
                        <span class="badge-anuncio-vivo"><i class="bi bi-broadcast"></i> En Vivo</span>
                        <h4>Webinar: Inteligencia Artificial & Python</h4>
                        <p>Integración práctica de modelos LLM, prompts estructurados y automatización de procesos.</p>
                        <div class="anuncio-meta">
                            <i class="bi bi-calendar3"></i>
                            <span>Jueves · 18:30 hs</span>
                        </div>
                        <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-anuncio-accion">Reservar lugar gratis</a>
                    </div>

                    <div class="lateral-card lateral-card-anuncio">
                        <span class="badge-anuncio-vivo badge-naranja"><i class="bi bi-broadcast"></i> En Vivo</span>
                        <h4>Taller: Seguridad Web & OWASP Top 10</h4>
                        <p>Buenas prácticas para prevenir vulnerabilidades comunes, sanitización de datos y sesiones seguras.</p>
                        <div class="anuncio-meta">
                            <i class="bi bi-calendar3"></i>
                            <span>Sábado · 15:00 hs</span>
                        </div>
                        <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-anuncio-accion">Reservar lugar gratis</a>
                    </div>

                </aside>

            </div>

        </div>

    </main>

</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
