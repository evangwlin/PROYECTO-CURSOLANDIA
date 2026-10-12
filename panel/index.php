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

$usuario_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = intval($_SESSION['usuario_id']);
} elseif (isset($_SESSION['usuario']['id'])) {
    $usuario_id = intval($_SESSION['usuario']['id']);
}
if ($usuario_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para acceder al panel.';
    header('Location: ' . $base_url . 'auth/ingresar.php');
    exit;
}

$usuarioBD = null;
if ($usuario_id > 0) {
    $resUser = mysqli_query($conexion, "SELECT * FROM usuario WHERE id = $usuario_id LIMIT 1");
    $usuarioBD = $resUser ? mysqli_fetch_assoc($resUser) : null;
}

$nombre_usuario = 'Usuario';
if (isset($usuarioBD['nombre']) && !empty($usuarioBD['nombre'])) {
    $nombre_usuario = $usuarioBD['nombre'];
} elseif (isset($_SESSION['usuario_nombre'])) {
    $nombre_usuario = $_SESSION['usuario_nombre'];
} elseif (isset($_SESSION['usuario']['nombre'])) {
    $nombre_usuario = $_SESSION['usuario']['nombre'];
}
$es_pro = ($usuario_id > 0) ? usuario_es_pro($usuario_id) : false;
$interesesUsuario = 'PHP, MySQL, Web';
if (isset($usuarioBD['intereses']) && !empty($usuarioBD['intereses'])) {
    $interesesUsuario = $usuarioBD['intereses'];
} elseif (isset($_SESSION['usuario']['intereses'])) {
    $interesesUsuario = $_SESSION['usuario']['intereses'];
}


$misInscripciones = [];
if ($usuario_id > 0) {
    $resIns = mysqli_query($conexion, "
        SELECT i.*, c.titulo, c.precio, c.etiquetas, c.imagen AS imagen, c.id AS curso_id, c.docente_id,
               u.nombre AS docente_nombre, u.apellido AS docente_apellido
        FROM inscripcion i
        JOIN curso c ON i.curso_id = c.id
        LEFT JOIN usuario u ON c.docente_id = u.id
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


$cursosDestacados = [];
$resDest = mysqli_query($conexion, "
    SELECT c.*, u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.plan AS docente_plan
    FROM curso c
    LEFT JOIN usuario u ON c.docente_id = u.id
    WHERE c.estado = 'publicado'
    ORDER BY (u.plan = 'pro') DESC, c.id DESC
    LIMIT 3
");
if ($resDest) {
    while ($fila = mysqli_fetch_assoc($resDest)) {
        $cursosDestacados[] = $fila;
    }
}

$interesesLista = [];
if (!empty($interesesUsuario)) {
    $limpios = str_replace(['#', ';'], ',', $interesesUsuario);
    $partesInt = explode(',', $limpios);
    foreach ($partesInt as $pInt) {
        $pInt = trim($pInt);
        if ($pInt !== '') {
            $interesesLista[] = $pInt;
        }
    }
}
$cursosRecomendados = [];

if (!empty($interesesLista)) {
    $whereParts = [];
    foreach ($interesesLista as $tag) {
        $tagEsc = mysqli_real_escape_string($conexion, $tag);
        $whereParts[] = "c.etiquetas LIKE '%$tagEsc%'";
    }
    $sqlRec = "
        SELECT c.*, u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.plan AS docente_plan
        FROM curso c
        LEFT JOIN usuario u ON c.docente_id = u.id
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


if (empty($cursosRecomendados)) {
    $resRecFallback = mysqli_query($conexion, "
        SELECT c.*, u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.plan AS docente_plan
        FROM curso c
        LEFT JOIN usuario u ON c.docente_id = u.id
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


$conteosCategorias = [];
$resCats = mysqli_query($conexion, "
    SELECT cat.slug, COUNT(c.id) AS total
    FROM categoria cat
    LEFT JOIN curso c ON (c.id_categoria = cat.id OR LOWER(c.categoria) = LOWER(cat.nombre) OR LOWER(c.categoria) = LOWER(cat.slug)) AND c.estado = 'publicado'
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
                                <span class="cat-top-cursos"><?php echo isset($conteosCategorias['diseno']) ? $conteosCategorias['diseno'] : 0; ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=programacion" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-code-slash"></i></div>
                                <span class="cat-top-nombre">Programación</span>
                                <span class="cat-top-cursos"><?php echo isset($conteosCategorias['programacion']) ? $conteosCategorias['programacion'] : 0; ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=comunicacion" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-people"></i></div>
                                <span class="cat-top-nombre">Comunicación</span>
                                <span class="cat-top-cursos"><?php echo isset($conteosCategorias['comunicacion']) ? $conteosCategorias['comunicacion'] : 0; ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=marketing" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-graph-up-arrow"></i></div>
                                <span class="cat-top-nombre">Marketing</span>
                                <span class="cat-top-cursos"><?php echo isset($conteosCategorias['marketing']) ? $conteosCategorias['marketing'] : 0; ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=finanzas" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-cash-stack"></i></div>
                                <span class="cat-top-nombre">Finanzas</span>
                                <span class="cat-top-cursos"><?php echo isset($conteosCategorias['finanzas']) ? $conteosCategorias['finanzas'] : 0; ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=ciencia" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-diagram-3"></i></div>
                                <span class="cat-top-nombre">Ciencia & Datos</span>
                                <span class="cat-top-cursos"><?php echo isset($conteosCategorias['ciencia']) ? $conteosCategorias['ciencia'] : 0; ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=ia" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-cpu"></i></div>
                                <span class="cat-top-nombre">Inteligencia Artificial</span>
                                <span class="cat-top-cursos"><?php echo isset($conteosCategorias['ia']) ? $conteosCategorias['ia'] : 0; ?> Cursos</span>
                            </a>

                            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=idiomas" class="tarjeta-categoria-top">
                                <div class="cat-top-icono"><i class="bi bi-translate"></i></div>
                                <span class="cat-top-nombre">Idiomas</span>
                                <span class="cat-top-cursos"><?php echo isset($conteosCategorias['idiomas']) ? $conteosCategorias['idiomas'] : 0; ?> Cursos</span>
                            </a>
                        </div>
                    </section>

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
                                    $iEtiq = isset($ins['etiquetas']) ? $ins['etiquetas'] : '';
                    $tags = []; if (!empty($iEtiq)) { foreach (explode(',', $iEtiq) as $pt) { $pt = trim($pt); if ($pt !== '') $tags[] = $pt; } }
                                    $tagPrincipal = !empty($tags) ? $tags[0] : '#Web';
                                    if (strpos($tagPrincipal, '#') !== 0) $tagPrincipal = '#' . $tagPrincipal;
                                    $iNom = isset($ins['docente_nombre']) ? $ins['docente_nombre'] : '';
                    $iApe = isset($ins['docente_apellido']) ? $ins['docente_apellido'] : '';
                    $docenteCompleto = trim($iNom . ' ' . $iApe);
                                    if (empty($docenteCompleto)) $docenteCompleto = 'Docente';
                                ?>
                                    <article class="tarjeta-progreso">
                                        <div class="progreso-header">
                                            <span class="progreso-tag"><?php echo htmlspecialchars($tagPrincipal); ?></span>
                                            <?php if ($ins['estado'] === 'pendiente'): ?>
                                                <span class="badge-estado-pago-pendiente">Pendiente de pago</span>
                                            <?php else: ?>
                                                <span class="badge-estado-pago-activa">Activa</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php 
                                            $linkPanelIns = ($ins['estado'] === 'activa') ? ($base_url . "cursos/aula.php?id=" . $ins['curso_id']) : ($base_url . "cursos/pagar.php?id=" . $ins['curso_id']);
                                            $textoBtnPanel = ($ins['estado'] === 'activa') ? 'Ir al Aula' : 'Pagar Curso';
                                            $pPorc = isset($ins['progreso_porcentaje']) ? (int)$ins['progreso_porcentaje'] : 0;
                    $progresoReal = max(0, min(100, $pPorc));
                                        ?>
                                        <h3><a href="<?php echo $linkPanelIns; ?>"><?php echo htmlspecialchars($ins['titulo']); ?></a></h3>
                                        <span class="progreso-docente">Prof. <?php if (!empty($ins['docente_id'])): ?><a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$ins['docente_id']; ?>" class="autor-nombre-link"><?php echo htmlspecialchars($docenteCompleto); ?></a><?php else: ?><?php echo htmlspecialchars($docenteCompleto); ?><?php endif; ?></span>
                                        <div class="barra-progreso-contenedor">
                                            <div class="barra-progreso-relleno" style="width: <?php echo $progresoReal; ?>%;"></div>
                                        </div>
                                        <div class="progreso-pie">
                                            <span class="progreso-leccion"><?php echo ($ins['estado'] === 'activa') ? 'Cursada activa' : 'Pendiente de confirmación'; ?></span>
                                            <a href="<?php echo $linkPanelIns; ?>" class="btn-continuar"><?php echo $textoBtnPanel; ?></a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="caja-vacia-panel">
                                <i class="bi bi-journal-plus"></i>
                                <h4>Todavía no estás inscripto a ningún curso</h4>
                                <p>¡Explorá nuestro catálogo y sumate a tu primera cátedra hoy mismo!</p>
                                <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-primario btn-alerta-accion">Explorar Cursos</a>
                            </div>
                        <?php endif; ?>
                    </section>

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
                                $cdNom = isset($cd['docente_nombre']) ? $cd['docente_nombre'] : '';
                                $cdApe = isset($cd['docente_apellido']) ? $cd['docente_apellido'] : '';
                                $docNombre = trim($cdNom . ' ' . $cdApe);
                                if (empty($docNombre)) $docNombre = 'Docente';
                                $cdEtiq = isset($cd['etiquetas']) ? $cd['etiquetas'] : '';
                                $tags = []; if (!empty($cdEtiq)) { foreach (explode(',', $cdEtiq) as $pt) { $pt = trim($pt); if ($pt !== '') $tags[] = $pt; } }
                                $esProDoc = (isset($cd['docente_plan']) && $cd['docente_plan'] === 'pro');
                                $cdImg = isset($cd['imagen']) ? $cd['imagen'] : '';
                                $cdCat = isset($cd['categoria']) ? $cd['categoria'] : '';
                                $imgPort = obtener_portada_curso($cdImg, $cdCat, (int)$cd['id']);
                            ?>
                                <article class="tarjeta-curso">
                                    <div class="curso-portada-card">
                                        <img src="<?php echo htmlspecialchars($imgPort); ?>" alt="<?php echo htmlspecialchars($cd['titulo']); ?>" class="curso-portada-img" loading="lazy" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imgPort); ?>';">
                                    </div>
                                    <div class="curso-info-card">
                                        <div class="curso-autor-meta">
                                            <?php if (!empty($cd['docente_id'])): ?>
                                                <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$cd['docente_id']; ?>" class="curso-autor-nombre autor-nombre-link"><?php echo htmlspecialchars($docNombre); ?></a>
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
                                $crNom = isset($cr['docente_nombre']) ? $cr['docente_nombre'] : '';
                                $crApe = isset($cr['docente_apellido']) ? $cr['docente_apellido'] : '';
                                $docNombre = trim($crNom . ' ' . $crApe);
                                if (empty($docNombre)) $docNombre = 'Docente';
                                $crEtiq = isset($cr['etiquetas']) ? $cr['etiquetas'] : '';
                                $tags = []; if (!empty($crEtiq)) { foreach (explode(',', $crEtiq) as $pt) { $pt = trim($pt); if ($pt !== '') $tags[] = $pt; } }
                                $esProDoc = (isset($cr['docente_plan']) && $cr['docente_plan'] === 'pro');
                                $crImg = isset($cr['imagen']) ? $cr['imagen'] : '';
                                $crCat = isset($cr['categoria']) ? $cr['categoria'] : '';
                                $imgPort = obtener_portada_curso($crImg, $crCat, (int)$cr['id']);
                            ?>
                                <article class="tarjeta-curso">
                                    <div class="curso-portada-card">
                                        <img src="<?php echo htmlspecialchars($imgPort); ?>" alt="<?php echo htmlspecialchars($cr['titulo']); ?>" class="curso-portada-img" loading="lazy" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imgPort); ?>';">
                                    </div>
                                    <div class="curso-info-card">
                                        <div class="curso-autor-meta">
                                            <?php if (!empty($cr['docente_id'])): ?>
                                                <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$cr['docente_id']; ?>" class="curso-autor-nombre autor-nombre-link"><?php echo htmlspecialchars($docNombre); ?></a>
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

                    <div class="lateral-cabecera-stat">
                        <h3 class="lateral-card-titulo">
                            <i class="bi bi-broadcast icono-primario"></i> Cursos y Talleres en Vivo
                        </h3>
                        <span class="badge-anuncio-vivo" >
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
