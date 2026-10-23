<?php
$titulo_pagina = 'Mis Cursos Creados';
$menu_activo = 'cursos';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$usuario_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = (int)$_SESSION['usuario_id'];
} elseif (isset($_SESSION['usuario']['id'])) {
    $usuario_id = (int)$_SESSION['usuario']['id'];
}
if ($usuario_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para ver tus cursos creados.';
    header('Location: ../auth/ingresar.php');
    exit;
}

$es_pro = usuario_es_pro($usuario_id);


$cursos = [];
$conexion = obtener_conexion();
$uid_seguro = intval($usuario_id);
$res = mysqli_query($conexion, "
    SELECT c.*,
           (SELECT COUNT(*) FROM inscripcion WHERE curso_id = c.id AND estado IN ('activa', 'pendiente')) AS inscriptos
    FROM curso c
    WHERE c.docente_id = $uid_seguro 
    ORDER BY c.id DESC
");
if ($res) {
    while ($fila = mysqli_fetch_assoc($res)) {
        $cursos[] = $fila;
    }
}


$total_cursos = count($cursos);
$total_alumnos = 0;
$total_cupos = 0;
$total_ingresos = 0;
$cursos_publicados = 0;
$cursos_pendientes = 0;
$cursos_rechazados = 0;

foreach ($cursos as $c) {
    $est = isset($c['estado']) ? $c['estado'] : 'publicado';
    if ($est === 'publicado') {
        $cursos_publicados++;
    } elseif ($est === 'pendiente') {
        $cursos_pendientes++;
    } elseif ($est === 'rechazado') {
        $cursos_rechazados++;
    }

    $inscriptos = isset($c['inscriptos']) ? (int)$c['inscriptos'] : 0;
    $cupo = isset($c['cupo_max']) ? (int)$c['cupo_max'] : 0;
    $precio = isset($c['precio']) ? (float)$c['precio'] : 0;
    
    $total_alumnos += $inscriptos;
    $total_cupos += $cupo;
    $total_ingresos += ($inscriptos * $precio);
}

$porcentaje_ocupacion = ($total_cupos > 0) ? round(($total_alumnos / $total_cupos) * 100) : 0;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido">

        <div class="mini-banner-cabecera">
            <div class="mini-banner-contenido">
                <div class="mini-banner-icono-caja">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <div class="mini-banner-migas">
                        <a href="<?php echo $base_url; ?>panel/index.php"><i class="bi bi-house-door"></i> Inicio</a>
                        <span class="miga-separador">/</span>
                        <span>Docente</span>
                        <span class="miga-separador">/</span>
                        <span class="miga-actual">Mis Cursos Creados</span>
                    </div>
                    <h1>Mis Cursos Creados</h1>
                    <p>Gestioná tus propuestas publicadas, cupos de alumnos e ingresos generados.</p>
                </div>
            </div>
            <div class="mini-banner-acciones">
                <a href="<?php echo $base_url; ?>cursos/crear.php" class="btn-primario">
                    <i class="bi bi-plus-lg"></i> Crear nuevo curso
                </a>
            </div>
        </div>

        <div class="inscripciones-aviso-cupo">
            <div class="aviso-cupo-info">
                <div class="aviso-cupo-texto">
                    <?php if ($es_pro): ?>
                        <strong>Plan PRO Activo · Creación de cursos ilimitada</strong>
                        <p>Podés publicar y gestionar todos los cursos y talleres que quieras sin restricciones de cupo.</p>
                    <?php else: 
                        $cupos_restantes_dictado = max(0, 3 - $cursos_publicados);
                        if ($cupos_restantes_dictado === 1) {
                            $frase_cupos_dictado = 'Te queda 1 cupo disponible.';
                        } elseif ($cupos_restantes_dictado === 0) {
                            $frase_cupos_dictado = 'No te quedan cupos disponibles.';
                        } else {
                            $frase_cupos_dictado = "Te quedan {$cupos_restantes_dictado} cupos disponibles.";
                        }
                    ?>
                        <strong>Cupos de dictado: <?php echo $cursos_publicados; ?> de 3 cursos activos utilizados</strong>
                        <p>En el Plan Gratuito podés dictar hasta 3 cursos al mismo tiempo. <?php echo $frase_cupos_dictado; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!$es_pro): ?>
                <a href="<?php echo $base_url; ?>perfil/configuracion.php#seccion-plan-pro" class="btn-cupo-pro">
                    Mejorar a Plan PRO
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($_SESSION['exito'])): ?>
            <div class="alerta-flash exito">
                <i class="bi bi-check-circle-fill"></i>
                <span><?php echo htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alerta-flash error">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>


        <section class="kpi-metricas-grid">
            
            <div class="kpi-card">
                <div class="kpi-card-top">
                    <span class="kpi-chip kpi-chip-rosa">
                        <i class="bi bi-arrow-up-right"></i> Activos
                    </span>
                </div>
                <div class="kpi-card-datos">
                    <div class="kpi-valor"><?php echo number_format($total_alumnos, 0, ',', '.'); ?></div>
                    <span class="kpi-titulo">Total alumnos inscriptos</span>
                    <div class="kpi-sub-area">
                        <span class="kpi-sub">En todos tus cursos</span>
                    </div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-card-top">
                    <span class="kpi-chip kpi-chip-rosa">
                        <i class="bi bi-check2"></i> Publicados
                    </span>
                </div>
                <div class="kpi-card-datos">
                    <div class="kpi-valor"><?php echo $cursos_publicados; ?></div>
                    <span class="kpi-titulo">Cursos publicados</span>
                    <div class="kpi-sub-area">
                        <span class="kpi-sub"><?php echo ($cursos_pendientes > 0) ? ($cursos_pendientes . ' en revisión') : 'Visibles en catálogo'; ?></span>
                    </div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-card-top">
                    <span class="kpi-chip kpi-chip-rosa">
                        <?php echo $total_alumnos; ?> / <?php echo $total_cupos; ?> vacantes
                    </span>
                </div>
                <div class="kpi-card-datos">
                    <div class="kpi-valor"><?php echo $porcentaje_ocupacion; ?>%</div>
                    <span class="kpi-titulo">Ocupación de cupos</span>
                    <div class="kpi-sub-area">
                        <div class="kpi-barra-fondo" title="<?php echo $porcentaje_ocupacion; ?>% de ocupación">
                            <div class="kpi-barra-progreso" style="width: <?php echo min(100, $porcentaje_ocupacion); ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-card-top">
                    <span class="kpi-chip kpi-chip-rosa">
                        Estimado
                    </span>
                </div>
                <div class="kpi-card-datos">
                    <div class="kpi-valor">$ <?php echo number_format($total_ingresos, 0, ',', '.'); ?></div>
                    <span class="kpi-titulo">Recaudación estimada</span>
                    <div class="kpi-sub-area">
                        <span class="kpi-sub">Por inscripciones activas</span>
                    </div>
                </div>
            </div>

        </section>

   
        <section class="tarjeta-panel gestion-cursos-card">
            
            <div class="gestion-cursos-toolbar">
                <div class="panel-cabecera-icono pagar-cabecera-mb">
                    <i class="bi bi-collection"></i>
                    <div>
                        <h3>Listado de mis cursos</h3>
                        <p class="panel-subtitulo-sec">Gestioná el contenido, revisá alumnos y editá la información.</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($cursos)): ?>
            <div class="tabla-responsive">
                <table class="tabla-gestion">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th class="texto-nowrap">Categoría</th>
                            <th class="texto-nowrap">Precio</th>
                            <th class="texto-nowrap">Inscriptos y Cupo</th>
                            <th class="texto-nowrap">Estado</th>
                            <th class="celda-acciones-der">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cursos as $curso): 
                            $ins = isset($curso['inscriptos']) ? (int)$curso['inscriptos'] : 0;
                            $cupo = isset($curso['cupo_max']) ? (int)$curso['cupo_max'] : 30;
                            $pct = ($cupo > 0) ? min(100, round(($ins / $cupo) * 100)) : 0;
                        ?>
                        <tr>
                            <td>
                                <div class="curso-celda-titulo">
                                    <?php 
                                    $curImg = isset($curso['imagen']) ? $curso['imagen'] : '';
                        $curCat = isset($curso['categoria']) ? $curso['categoria'] : '';
                        $img_celda = obtener_portada_curso($curImg, $curCat, (int)$curso['id']);
                                    ?>
                                    <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $curso['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($img_celda); ?>" alt="<?php echo isset($curso['titulo']) ? htmlspecialchars($curso['titulo']) : ''; ?>" class="curso-celda-img" loading="lazy" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($img_celda); ?>';">
                                    </a>
                                    <div>
                                        <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $curso['id']; ?>" class="curso-nombre-tabla-link">
                                            <strong class="curso-nombre-tabla"><?php echo htmlspecialchars($curso['titulo']); ?></strong>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="texto-nowrap">
                                <span class="badge-categoria-tabla">
                                    <i class="bi <?php echo isset($curso['icono']) ? htmlspecialchars($curso['icono']) : 'bi-tag'; ?>"></i>
                                    <?php echo isset($curso['categoria']) ? htmlspecialchars($curso['categoria']) : 'General'; ?>
                                </span>
                            </td>
                            <td class="texto-nowrap">
                                <span class="precio-valor-tabla">
                                    <?php 
                                    if (empty($curso['precio']) || $curso['precio'] == 0) {
                                        echo '<span class="texto-gratis">Gratis</span>';
                                    } else {
                                        echo '$&nbsp;' . number_format($curso['precio'], 0, ',', '.');
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="celda-cupos-bloque">
                                    <div class="cupos-fila-texto">
                                        <span class="cupos-conteo"><strong><?php echo $ins; ?></strong> / <?php echo $cupo; ?> alumnos</span>
                                        <span class="cupos-porcentaje"><?php echo $pct; ?>%</span>
                                    </div>
                                    <div class="cupos-barra-fondo">
                                        <div class="cupos-barra-fill" style="width: <?php echo $pct; ?>%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="texto-nowrap">
                                <?php $est = isset($curso['estado']) ? $curso['estado'] : 'publicado'; ?>
                                <?php if ($est === 'publicado'): ?>
                                    <span class="badge-estado publicado">
                                        <i class="bi bi-check-circle-fill"></i> Publicado
                                    </span>
                                <?php elseif ($est === 'pendiente'): ?>
                                    <span class="badge-estado pendiente">
                                        <i class="bi bi-clock-history"></i> En Revisión
                                    </span>
                                <?php elseif ($est === 'rechazado'): ?>
                                    <span class="badge-estado rechazada">
                                        <i class="bi bi-x-circle-fill"></i> Rechazado
                                    </span>
                                <?php else: ?>
                                    <span class="badge-estado borrador">
                                        <i class="bi bi-pencil"></i> <?php echo htmlspecialchars(ucfirst($est)); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="celda-acciones-der">
                                <div class="acciones-fila celda-acciones-der">
                                    <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso['id']; ?>" class="btn-tabla-pildora btn-pildora-aula" title="Ingresar al Aula Virtual y Foro">
                                        <i class="bi bi-mortarboard-fill"></i> Aula
                                    </a>
                                    <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $curso['id']; ?>" class="btn-tabla-pildora" title="Ver curso público">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                    <a href="<?php echo $base_url; ?>cursos/editar.php?id=<?php echo $curso['id']; ?>" class="btn-tabla-pildora btn-pildora-editar" title="Editar curso">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <a href="<?php echo $base_url; ?>cursos/alumnos.php?id=<?php echo $curso['id']; ?>" class="btn-tabla-pildora btn-pildora-alumnos" title="Ver alumnos inscriptos">
                                        <i class="bi bi-people"></i> Alumnos (<?php echo $ins; ?>)
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="docente-acciones-wrap">
                <div class="inscripciones-vacio">
                    <div class="vacio-icono">
                        <i class="bi bi-folder-plus"></i>
                    </div>
                    <h3>Todavía no creaste cursos</h3>
                    <p>Publicá tu primera propuesta para comenzar a recibir alumnos.</p>
                    <a href="<?php echo $base_url; ?>cursos/crear.php" class="btn-primario">Crear nuevo curso</a>
                </div>
            </div>
            <?php endif; ?>
        </section>

    </main>

</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
